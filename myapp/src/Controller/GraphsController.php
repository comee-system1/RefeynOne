<?php
namespace App\Controller;


use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\Error\Debugger;
/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class GraphsController extends AppController
{

    const RefeynOne = "RefeynOne";
    const Mesurement = "Mesurement";
    const noname = "noname";
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        ini_set("memory_limit", "-1");
        $this->uAuth = $this->Auth->user();
        if(!$this->uAuth){
            return $this->redirect(['controller'=>'/','action' => '/']);
        }
        $this->array_smooth = Configure::read("array_smooth");
        $this->array_graf_type = Configure::read("array_graf_type");
        $this->session = $this->getRequest()->getSession();;

        $this->Graphes = $this->loadModel("Graphes");
        $this->GrapheDatas = $this->loadModel("GrapheDatas");
        $this->GraphePoints = $this->loadModel("GraphePoints");
        $this->GrapheDisplays = $this->loadModel("GrapheDisplays");
        $this->SopDefaults = $this->loadModel("SopDefaults");
        $this->SopAreas = $this->loadModel("SopAreas");
        $this->UploadComponent = $this->loadComponent("Upload",$this->uAuth);
        $this->set("uAuth",$this->uAuth);
        $this->set("array_smooth",$this->array_smooth);
        $this->set("bottom","");
    }

    public function index($id = ""){
        //IDが無いときは初期データの為新規登録を行う
        if(!$id){
            $this->add();
        }
        $this->set("id",$id);

    }
    public function step2($id){
        $this->editsop("",$id);


        //初期状態でエリアを5個登録する
        $count = $this->SopAreas->find()->where([
            'graphe_id'=>$id
        ])->count();
        if($count <= 0 ){
            for($i=1;$i<=5;$i++){
                $SopAreas = $this->SopAreas->newEntity();
                $SopAreas->user_id   = $this->uAuth[ 'id' ];
                $SopAreas->graphe_id = $id;
                $SopAreas->name = "";
                $SopAreas->minpoint = 0;
                $SopAreas->maxpoint = 0;
                $this->SopAreas->save($SopAreas);
            }
        }

        $SopDefaults = $this->SopDefaults->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$id,
        ])->first();

        $SopAreas = $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$id,
        ])->toArray();



        //セッションの登録
        $this->session->write('step', "step2");
        $this->set("id",$id);
       // $this->set("SopDefaults",$SopDefaults);
        $this->set(compact('SopDefaults'));
        $this->set(compact('SopAreas'));
        $this->set("sopdefaultid",$SopDefaults[ 'id' ]);

    }


    public function step3($graphe_id){
        //セッションの確認
        $step = $this->session->read('step');
        $this->session->write('step', "step3");

        $SopAreas = $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$graphe_id,
        ])->toArray();
        $this->set(compact('SopAreas'));


        //グラフデータ取得
        $connection = ConnectionManager::get('default');
        $user_id = $this->uAuth['id'];

        $sql = " SELECT
              id,
              label
            FROM graphe_datas WHERE
                user_id='${user_id}' AND
                graphe_id = '${graphe_id}' AND
                disp != 0
        ";
        $graphe_data = $connection->execute($sql)->fetchall('assoc');


        //グラフ横軸
        $sopDefaults = $this->SopDefaults->find()->where([
                    'user_id'=>$user_id,
                    'graphe_id'=>$graphe_id
                ])->first();

        $defaultpoint = $sopDefaults[ 'defaultpoint' ];
        $dispareamax  = $sopDefaults[ 'dispareamax' ];
        $binsize      = $sopDefaults[ 'binsize' ];
        $smooth       = $sopDefaults[ 'smooth' ];

        $line = [];
        $compares = [];
        $no = 0;
        for($i=$defaultpoint;$i<=$dispareamax;$i=$i+$binsize){
            $line[] = $i;
            $compares[$no]['min'] = $i;
            $compares[$no]['max'] = $i+$binsize;
            $no++;
        }
        $binline = implode(",",$line);
        $this->set("binline",$binline);
        $this->set("defaultpoint",$defaultpoint);
        $this->set("dispareamax",$dispareamax);
        $this->set("binsize",$binsize);
        $this->set("smooth",$smooth);


        //グラフ取得範囲
        //比較対象の作成
//var_dump($graphe_data,$compares);
        $data = [];
        $insert = "";

        //登録データの確認
/*
        $query = $this->GrapheDisplays->find()->where([
            'user_id'=>$user_id,
            'graphe_id'=>$graphe_id
            ])->count();
*/

        if($step != "step2"){
            //既に登録済みなので何もしない
        }else{
            //グラフ用の表示データを削除
            $this->GrapheDisplays->deleteAll(
                ['graphe_id'=>$graphe_id]
            );


            $sql = "
            SELECT a.*, (";
            foreach($compares as $k=>$comp){
                $sql .= " ranges_".$comp['min']."_".$comp['max']." + ";
            }
            $sql .= "0) as total2";
            $sql .= " FROM (
            SELECT graphe_data_id";
            foreach($compares as $k=>$comp){
                $ctr = ($comp['min']+$comp['max'])/2;
                $sql .= ", SUM(CASE WHEN pointdata >= ".$comp['min']." AND pointdata < ".$comp['max']." THEN 1 ELSE 0 END) AS range_".$comp['min']."_".$comp['max'];
                $sql .= ", ".$ctr." * SUM(CASE WHEN pointdata >= ".$comp['min']." AND pointdata < ".$comp['max']." THEN 1 ELSE 0 END) AS ranges_".$comp['min']."_".$comp['max'];
            }
            $sql .= " ,SUM(pointdata) AS total ";
            $sql .= "
                FROM graphe_points
                WHERE graphe_id = '${graphe_id}'
                GROUP BY graphe_data_id
                ) as a
            ";
            $graphe_points = $connection->execute($sql)->fetchall('assoc');

            foreach($graphe_points as $value){

                $graphe_data_id = $value[ 'graphe_data_id' ];
                $total = $value[ 'total' ];
                $total2 = $value[ 'total2' ];
                foreach($compares as $k=>$comp){
                    $rg = "range_".$comp[ 'min' ]."_".$comp[ 'max' ];
                    $rg2 = "ranges_".$comp[ 'min' ]."_".$comp[ 'max' ];
                    $counts1 = $value[ $rg ];

                    $min = $comp[ 'min' ];
                    $max = $comp[ 'max' ];
                    $ctr = ($min+$max)/2;
                    $counts2 = $value[ $rg2 ];
                    $counts3 = ($counts1 == 0)?0:round($counts1/$total,2);
                    $counts4 = ($counts2 == 0)?0:round($counts2/$total2,2);
                    $insert .= "(
                        '".$user_id."',
                        '".$graphe_id."',
                        '".$graphe_data_id."',
                        '".$counts1."',
                        '".$counts2."',
                        '".$counts3."',
                        '".$counts4."',
                        '".$max."',
                        '".$min."',
                        '".$ctr."',
                        '".date('Y-m-d H:i:s')."',
                        '".date('Y-m-d H:i:s')."'
                    ),";
                }
            }

/*
            foreach($graphe_data as $key=>$value){

                foreach($compares as $k=>$comp){
                    $graphePoints = "";
                    $graphePoints = $this->GraphePoints->find();
                    $graphePoints = $graphePoints
                        ->select([
                            'count'=>$graphePoints->func()->count( 'id' )
                        ])
                        ->where([
                        'user_id'=>$user_id,
                        'graphe_id'=>$graphe_id,
                        'graphe_data_id'=>$value[ 'id' ],
                        'pointdata >= '.$comp[ 'min' ],
                        'pointdata < '.$comp[ 'max' ],
                    ])->first();


                    $center[$key][$k] = ($comp[ 'min' ]+$comp[ 'max' ])/2;
                    $cnt[$key][$k] = $graphePoints->count;

                    $cnt2[$key][$k] = $graphePoints->count*$center[$key][$k];

                    $s = (isset($sum[$key]))?$sum[$key]:0;
                    $sum = sprintf("%d",$s+$graphePoints->count);
                    if($cnt[$key][$k] == 0 || $sum == 0){
                        $cnt3[$key][$k] = 0;
                    }else{
                        $cnt3[$key][$k] = round(((int)$cnt[$key][$k]/(int)$sum),5);
                    }

                    $s2 = (isset($sum2[$key]))?$sum2[$key]:0;
                    $sum2 = sprintf("%d",$s2+($cnt[$key][$k]*$center[$key][$k]));
                    if($cnt2[$key][$k] == 0 || $sum2 == 0){
                        $cnt4[$key][$k] = 0;
                    }else{
                        $cnt4[$key][$k] = round(((int)$cnt2[$key][$k]/(int)$sum2),5);
                    }
                    //var_dump($graphePoints->count);
                    //exit();

                }



                // $data[$key][$k] = $graphePoints->count;
                //    $this->log("Label=>".$value[ 'label' ]."/count=>".$graphePoints->count."/min=>:".$comp['min']."/max=>:".$comp['max'],'debug');
                foreach($compares as $k=>$comp){

                    //smoothの設定
                    $num = 0;
                    $avecount = 0;
                    $avecount2 = 0;
                    $avecount3 = 0;
                    $avecount4 = 0;
                    for($i=$start-$plus;$i<=$start+$plus;$i++){
                        $avecount += (isset($cnt[$key][$i]))?$cnt[$key][$i]:0;
                        $avecount2 += (isset($cnt2[$key][$i]))?$cnt2[$key][$i]:0;
                        $avecount3 += (isset($cnt3[$key][$i]))?$cnt3[$key][$i]:0;
                        $avecount4 += (isset($cnt4[$key][$i]))?$cnt4[$key][$i]:0;
                        $num++;
                    }

                    $start += $plus;

                    $ave1 = ($avecount > 0 )?round($avecount/$smooth,5):0;
                    $ave2 = ($avecount2 > 0 )?round($avecount2/$smooth,5):0;
                    $ave3 = ($avecount3 > 0 )?round($avecount3/$smooth,5):0;
                    $ave4 = ($avecount4 > 0 )?round($avecount4/$smooth,5):0;


                    $ctr = $center[$key][$k];
                    $counts1 = $cnt[$key][$k];
                    $counts2 = $cnt2[$key][$k];
                    //$counts2 = $counts1*$ctr;
                   // $counts3 = round($counts1/(int)$sum[$key],5);
                    $counts3 = $cnt3[$key][$k];
                    $counts4 = $cnt4[$key][$k];
                    //$counts4 = round($counts2/(int)$sum2[$key],5);
                    $insert .= "(
                        '".$user_id."',
                        '".$graphe_id."',
                        '".$value[ 'id' ]."',
                        '".$counts1."',
                        '".$counts2."',
                        '".$counts3."',
                        '".$counts4."',
                        '".$ave1."',
                        '".$ave2."',
                        '".$ave3."',
                        '".$ave4."',
                        '".$comp[ 'max' ]."',
                        '".$comp[ 'min' ]."',
                        '".$ctr."',
                        '".date('Y-m-d H:i:s')."',
                        '".date('Y-m-d H:i:s')."'
                    ),";
                }
            }
*/
            //取得したデータをデータパターン毎に登録処理を行う
            //次回アクセス時、表示データ切り替えの際の処理を高速にする
            if($insert){
                $sql = "
                    INSERT INTO graphe_displays (
                        user_id,
                        graphe_id,
                        graphe_data_id,
                        counts1,
                        counts2,
                        counts3,
                        counts4,
                        max,
                        min,
                        center,
                        created,
                        modified
                    ) VALUES ";
                $sql .= trim($insert,",");
                $connection->execute($sql);
            }

        }

        //表示用のデータ取得を行う
        $sql = "
            SELECT
                GROUP_CONCAT( id ) as line
            FROM
                graphe_datas
            WHERE
                user_id='${user_id}' AND
                graphe_id = '${graphe_id}' AND
                disp != 0
        ";
        $rlt = $connection->execute($sql)->fetch('assoc');
        $line = $rlt['line'];
        $sql = "
            SELECT
                GROUP_CONCAT( counts1 ) as cnt
            FROM
                graphe_displays
            WHERE
                user_id='${user_id}' AND
                graphe_id = '${graphe_id}' AND
                graphe_data_id IN (${line})
            GROUP BY graphe_data_id
            ";
        $display = $connection->execute($sql)->fetchall('assoc');

        $graphe_point = [];
        foreach($display as $key=>$value){
            $graphe_point[]['point'] = $this->setSmooth($value,$smooth);
           // $graphe_point[]['point'] = $value[ 'cnt' ];
        }

        $this->set("id",$graphe_id);
        $this->set("graphe_data",$graphe_data);
        $this->set("graphe_point",$graphe_point);
        $this->set("smooth",$smooth);

    }

    public function setSmooth($array,$smooth){
        $ex = explode(",",$array[ 'cnt' ]);
        $count = count($ex);
        $start = 0-floor($smooth/2);
        $end = $count+floor($smooth/2);
        $list = [];
        for($i=$start;$i<=$end;$i++){
            $numeric=0;
            for($j=$i;$j<$i+$smooth;$j++){
                $numeric += (isset($ex[$j]))?$ex[$j]:0;
            }
            if($i >= 0){
                $list[] = $numeric/$smooth;
            }
            if($count < $i) break;
        }
        $imp = implode(",",$list);

        return $imp;
    }

    public function step3Graph($graphe_id = ""){
        $grafData = $this->GrapheDatas->find();

        $this->set("id",$graphe_id);
        $this->set("grafData",$grafData);
    }
    public function step4(){


    }

    public function setSop($graphe_id){
        $this->autoRender=false;
        $SopAreas = $this->SopAreas->newEntity();
        $SopAreas->user_id = $this->uAuth['id'];
        $SopAreas->graphe_id = $graphe_id;
        $SopAreas->name = self::noname;
        $SopAreas->minpoint = 0;
        $SopAreas->maxpoint = 0;
        $SopAreas->edit = 1;
        $this->SopAreas->save($SopAreas);
    }
    public function getSop($graphe_id){
        //SOPエリアデータ取得
        $SopAreas = $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$graphe_id,
        ])->toArray();
        header('Content-type: application/json');
        echo json_encode($SopAreas,JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function upload($graphe_id,$type="",$label=""){
        $this->autoRender = false;

        if($this->request->is('ajax')){
            if($this->request->getData('upfile')[ 'error' ] === 0 ){
                //ファイルアップロード
                if($type == "sop"){
                    $this->UploadComponent->fileUploadSop($graphe_id);
                }else
                if($type == "mesurement" ){
                    $this->UploadComponent->fileUploadMesurement($graphe_id,self::Mesurement);
                }else{
                    $this->UploadComponent->fileUploadRefeynOne($graphe_id,self::RefeynOne,$label);
                }
            }else{
                echo 1;
            }
            exit();
        }

    }

    //グラフ画面からcsvエクスポート
    public function outputGraphe($graphe_id){
        $this->autoRender = false;


        $data = $this->GrapheDatas->find('all',[])
        ->where(
            [
                'GrapheDatas.graphe_id'=>$graphe_id,
                'GrapheDatas.user_id'=>$this->uAuth['id'],
            ]
        )->toArray();

        $GrapheDisplays = $this->GrapheDisplays->find('all')
        ->where(
            [
                'GrapheDisplays.graphe_id'=>$graphe_id,
                'GrapheDisplays.user_id'=>$this->uAuth['id'],
            ]
        )->toArray();

        $points = [];
        $n = 0;
        foreach($GrapheDisplays as $key=>$value){
            $points[$value->graphe_data_id][$n][ 'count1' ] = $value->counts1;
            $points[$value->graphe_data_id][$n][ 'min' ] = $value->min;
            $points[$value->graphe_data_id][$n][ 'max' ] = $value->max;
            $points[$value->graphe_data_id][$n][ 'center' ] = $value->center;
            $n++;
        }

        //保存場所
        $filename = date('YmdHis') . '_graf.csv';
        $file = WWW_ROOT.'csv/' .$filename;
        $f = fopen($file, 'w');
        $list = [];
        $list[0][] = mb_convert_encoding('階級','SJIS','UTF-8');
        $list[0][] = mb_convert_encoding('階級最小値','SJIS','UTF-8');
        $list[0][] = mb_convert_encoding('階級最大値','SJIS','UTF-8');
        $list[0][] = mb_convert_encoding('階級中央値','SJIS','UTF-8');
        $i=0;
        foreach($data as $key=>$value){
            $list[$i][] = mb_convert_encoding($value->label,'SJIS','auto');
        }
        $i++;
        $first = true;
        foreach($data as $key=>$value){
            foreach($points[$value->id] as $k=>$val){
                if($first){
                    $list[$i][] = $val['min'];
                    $list[$i][] = $val['min'];
                    $list[$i][] = $val['max'];
                    $list[$i][] = $val['center'];
                }
                $list[$i][] = $val['count1'];

                $i++;
            }
            $i=1;
            $first = false;
        }
        foreach($list as $key=>$value){
            fputcsv($f, $value);
        }


        fclose($f);

        return $this->response->withFile(
            $file,
            [
              'download'=>true,
            ]
          );

    }

    //CSV出力
    public function outputMesurement($graphe_id){
        $this->autoRender = false;
        $data = $this->GrapheDatas->find('all',['contain'=>'GraphePoints'])
        ->where(
            [
                'GrapheDatas.graphe_id'=>$graphe_id,
                'GrapheDatas.user_id'=>$this->uAuth['id'],
//                'GrapheDatas.filename'=>self::Mesurement
            ]
        )->toArray();


        $list = [];
        $title = [];
        $valueid = 0;
        $no = 0;
        foreach($data as $key=>$value){
            if($valueid != $value->id) $no = 0;
            if($no == 0){
                $list[ $value->id][] = $value[ 'label' ];
            }
            $list[ $value->id ][] = $value['graphe_point'][ 'pointdata' ];

            $valueid = $value->id;
            $no++;
        }


        //保存場所
        $filename = date('YmdHis') . '.csv';
        $file = WWW_ROOT.'csv/' .$filename;
        $f = fopen($file, 'w');
        foreach($list as $key=>$value){
            fputcsv($f, $value);
        }


        fclose($f);

        return $this->response->withFile(
            $file,
            [
              'download'=>true,
            ]
          );

    }
    //step1のデータ一覧表示部分
    public function graphdata($graphe_id){

        $this->autoRender = false;
        $data = $this->GrapheDatas->find()->where(
            [
                'graphe_id'=>$graphe_id,
                'user_id'=>$this->uAuth['id'],
            ]
        )->toArray();
        header('Content-type: application/json');
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function add()
    {
        $graphe = $this->Graphes->newEntity();
        $graphe->user_id = $this->uAuth['id'];
        $graphe->name = time();
        if ($this->Graphes->save($graphe)) {

            return $this->redirect(['action' => 'index',$graphe->id]);
        }
        $this->Flash->error(__('登録に失敗しました'));
        return $this->redirect(['controller'=>'users','action' => 'index']);

    }
    public function edit($id = null)
    {
        $GrapheDatas = $this->GrapheDatas->get($id, [
            'contain' => [],
        ]);
        $GrapheDatas = $this->GrapheDatas->patchEntity($GrapheDatas, $this->request->getData());
        $this->GrapheDatas->save($GrapheDatas);
    }
    public function editDispStatus()
    {
        $this->autoRender = false;

        $id = $this->request->getData("id");
        $chk = $this->request->getData("chk");
        $userid = $this->uAuth['id'];

        $GrapheDatas = $this->GrapheDatas->find()->where([
            'user_id'=>$userid,
            'id'=>$id
        ])->first();
        $flag = 0;
        if($chk === "true" ) $flag = 1;
        $set[ 'disp' ] = $flag;

        $GrapheDatas->disp = $flag;
        $this->GrapheDatas->save($GrapheDatas);
        exit();
    }
    public function editsop($id = null,$graphe_id = "")
    {

        if($id){
            $this->autoRender = false;
            $SopDefaults = $this->SopDefaults->get($id, [
                'contain' => [],
            ]);
        }else{
            $SopDefaults = $this->SopDefaults->find()->where([
                "user_id"=>$this->uAuth['id'],
                "graphe_id"=>$graphe_id
                ])->first();
            if(empty($SopDefaults)){
                $SopDefaults = $this->SopDefaults->newEntity();
            }else{
                //idが無くデータがあれば処理を行わない
                return false;
            }
        }
        $set = [];
        if($this->request->getData("name")){
            $set[$this->request->getData('name')] = $this->request->getData('value');
        }
        $set[ 'user_id' ] = $this->uAuth['id'];
        if($graphe_id > 0){
            $set[ 'graphe_id' ] = $graphe_id;
            $set[ 'defaultpoint' ] = 0;
            $set[ 'dispareamax' ] = 0;
            $set[ 'binsize' ] = 0;
            $set[ 'smooth' ] = 0;
        }
        $SopDefaults = $this->SopDefaults->patchEntity($SopDefaults, $set,['validate'=>false]);
        $this->SopDefaults->save($SopDefaults);
    }
    public function editsoparea($id = null)
    {

        $this->autoRender = false;
        $set = [];
        if($id > 0 ){
            $SopAreas = $this->SopAreas->get($id, [
            'contain' => [],
            ]);
        }

        if($this->request->getData("name")){
            $set[$this->request->getData('name')] = $this->request->getData('value');
        }

        $SopAreas = $this->SopAreas->patchEntity($SopAreas, $set,['validate'=>false]);
        $this->SopAreas->save($SopAreas);
    }


    public function delete($graph_id,$graph_data_id)
    {
        $graphdata = $this->GrapheDatas->find()->where([
            'id'=>$graph_data_id,
            'user_id'=>$this->uAuth[ 'id' ],
            'graphe_id'=>$graph_id,
        ])->first();


        if ($this->GrapheDatas->delete($graphdata)) {
            $this->GraphePoints->deleteAll([
                'graphe_data_id'=>$graph_data_id,
                'user_id'=>$this->uAuth[ 'id' ]
            ]);

            $this->Flash->success(__('データの削除を行いました。'));
        } else {
            $this->Flash->error(__('データの削除に失敗しました。'));
        }
        return $this->redirect(['action' => '/index/',$graph_id]);
    }


    public function getAreaTable($id){
        $this->autoRender = false;
        $connection = ConnectionManager::get('default');

        $areas= $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$id,
        ])->toArray();

        $user_id = $this->uAuth['id'];
        $SopAreas[ 'areas' ] = $areas;

        $sql = " SELECT ";
            foreach($areas as $k=>$value){
                $sql .= " SUM( CASE WHEN disp.counts1 >= ".$value[ 'minpoint' ]." AND disp.counts1 <".$value[ 'maxpoint' ]." THEN disp.counts1 ELSE 0 END ) AS sum_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ]
                .",";

                $sql .= " GROUP_CONCAT( CASE WHEN disp.counts1 >= ".$value[ 'minpoint' ]." AND disp.counts1 <".$value[ 'maxpoint' ]." THEN disp.counts1 ELSE NULL END ) AS groupLine_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";
            }
        $sql .= "

                graphe_data_id,
                data.counts as total,
                data.label as label
            FROM
                graphe_displays as disp
                LEFT JOIN graphe_datas as data ON data.id  = disp.graphe_data_id
            where
                disp.user_id = ${user_id} AND
                disp.graphe_id = ${id}
                GROUP BY disp.graphe_data_id
        ";

        $list = $connection->execute($sql)->fetchall('assoc');
        $lists = [];
        $label = [];
        $median = 0;
        $mode = [];
        $lot = 0;
        $ave = 0;
        foreach($list as $key=>$value){
            $total = $value['total'];
            $label[$key]['label'] = $value[ 'label' ];
            $no = 0;
            foreach($value as $k=>$val){
                if(preg_match("/^groupLine_/",$k)){
                    $ex = [];
                    $ex = explode(",",$val);
                   // $lists[$key][$k][ 'median' ] = $this->median($ex);
                    $median = $this->median($ex);
                    $mode = $this->mode($ex);
                  //  $lists[$key][$k][ 'mode' ] = $mode[0];
                }
                if(preg_match("/^sum_/",$k)){
                //    $lists[$key][$k][ 'lot' ] = round($val/$total*100,2);
                //    $lists[$key][$k][ 'ave' ] = round(($val == 0)?0:$total/$val,2);
                    $lot = round($val/$total*100,2);
                    $ave = round(($val == 0)?0:$total/$val,2);

                    $lists[$key][$no][ 'lot' ] = $lot;
                    $lists[$key][$no][ 'ave' ] = $ave;
                    $lists[$key][$no][ 'median' ] = $median;
                    if(!empty($mode[0])){
                        $lists[$key][$no][ 'mode' ] = $mode[0];
                    }else{
                        $lists[$key][$no][ 'mode' ] = 0;
                    }
                    $no++;

                }

            }

        }
        $SopAreas[ 'label' ] = $label;
        $SopAreas[ 'lists' ] = $lists;
        header('Content-type: application/json');
        echo json_encode($SopAreas,JSON_UNESCAPED_UNICODE);
        exit();

    }

    public function createDispGraph($id = ""){
        $this->autoRender = false;
        $connection = ConnectionManager::get('default');
        $user_id = $this->uAuth['id'];
        preg_match("/[0-9]/",$this->request->getData("basic"),$basic);
        preg_match("/[0-9]/",$this->request->getData("display"),$display);
        $code = $basic[0].$display[0];
        $clum = $this->array_graf_type[$code];
        $sql = "
            SELECT a.* FROM (
            SELECT
                GROUP_CONCAT( ".$clum." ) as cnt,
                gdisplay.graphe_data_id,
                gdata.label,
                gdata.disp
            FROM
                graphe_displays as gdisplay
                LEFT JOIN graphe_datas as gdata ON gdisplay.graphe_data_id = gdata.id
            WHERE
                gdisplay.user_id='${user_id}' AND
                gdisplay.graphe_id = '${id}' AND
                gdata.disp != 0
            GROUP BY gdisplay.graphe_data_id
                ) as a
            ORDER BY a.disp ASC
            ";
        $display = $connection->execute($sql)->fetchall('assoc');
        header('Content-type: application/json');
        echo json_encode($display,JSON_UNESCAPED_UNICODE);
        exit();
    }


    //平均値を求める関数
    public function average(array $values)
    {
        return (float) (array_sum($values) / count($values));
    }

    public function variance(array $values)
    {
        // 平均値を求める
        $ave = $this->average($values);

        $variance = 0.0;
        foreach ($values as $val) {
            $variance += pow($val - $ave, 2);
        }
        return (float) ($variance / count($values));
    }

    public function standardDeviation(array $values)
    {
        // 分散を求める
        $variance = $this->variance($values);

        // 分散の平方根
        return (float) sqrt($variance);
    }

    //偏差値を求める
    public function standardScore( $target, array $arr)
    {
        return ( $target - $this->average($arr) ) / $this->standardDeviation($arr) * 10 + 50;
    }

    /*
    * 最頻値を求める
    */
    public function mode(array $values)
    {
    	//最頻値を求める。それぞれの頻出回数を計算して配列に入れる。
    	$data = array_count_values($values);
    	$max = max($data);//配列から最大値を取得する。
    	$result[0] = array_keys($data,$max);
    	return $result[0];
    }
    /*
    *中央値を求める関数
    */
    public function median(array $values){

		sort($values);
		if (count($values) % 2 == 0){
			return (($values[(count($values)/2)-1]+$values[((count($values)/2))])/2);
		}else{
			return ($values[floor(count($values)/2)]);
		}
	}


}
