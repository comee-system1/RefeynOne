<?php
namespace App\Controller;


use Cake\Event\Event;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\Error\Debugger;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class GraphsController extends AppController
{

    const RefeynOne = "Refeyn";
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

    public function beforeStep3($graphe_id){

        if($this->request->is('ajax')){
            $this->autoRender=false;


            //セッションの確認
            $step = $this->session->read('step');
            $this->session->write('step', "step3");


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
                ORDER BY disp >0 DESC,disp
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

            $compares = [];
            $no = 0;
            for($i=$defaultpoint;$i<=$dispareamax;$i=$i+$binsize){

                $compares[$no]['min'] = $i;
                $compares[$no]['max'] = $i+$binsize;
                $no++;
            }

            //グラフ取得範囲
            //比較対象の作成
            $data = [];
            $insert = "";

            if( $step != "step2"){
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
                $sql .= "0) as total2 ,(";
                foreach($compares as $k=>$comp){
                    $sql .= " range_".$comp['min']."_".$comp['max']." + ";
                }
                $sql .= "0) as total";

                $sql .= " FROM (
                SELECT graphe_data_id";
                foreach($compares as $k=>$comp){
                    $ctr = ($comp['min']+$comp['max'])/2;
                    $sql .= ", SUM(CASE WHEN pointdata >= ".$comp['min']." AND pointdata < ".$comp['max']." THEN 1 ELSE 0 END) AS range_".$comp['min']."_".$comp['max'];

                    $sql .= ", ".$ctr." * SUM(CASE WHEN pointdata >= ".$comp['min']." AND pointdata < ".$comp['max']." THEN 1 ELSE 0 END) AS ranges_".$comp['min']."_".$comp['max'];
                }
                //$sql .= " ,SUM(pointdata) AS total ";
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
                        $counts3 = ($counts1 == 0)?0:round($counts1/$total,5);
                        $counts4 = ($counts2 == 0)?0:round($counts2/$total2,5);
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

            exit();
        }
        $this->set("id",$graphe_id);
    }


    public function step3($graphe_id){

        $connection = ConnectionManager::get('default');
        $user_id = $this->uAuth['id'];

        $sql = " SELECT
            id,
            label
            FROM graphe_datas WHERE
                user_id='${user_id}' AND
                graphe_id = '${graphe_id}' AND
                disp != 0
            ORDER BY disp >0 DESC,disp
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
        //初期全範囲を入れる
        $sopareas = $this->SopAreas->find()->where([
            "user_id"=>$user_id,
            "graphe_id"=>$graphe_id
            ])->first();
        $set[ 'minpoint' ] = $sopDefaults[ 'defaultpoint' ];
        $set[ 'maxpoint'  ] = $sopDefaults[ 'dispareamax' ];
        $sopareas = $this->SopAreas->patchEntity($sopareas, $set,['validate'=>false]);
        $this->SopAreas->save($sopareas);

        $SopAreas = $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$graphe_id,
        ])->toArray();
        $this->set(compact('SopAreas'));


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
        //こちらは初回ページになるのでcounts1固定となる
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
        for($i=$start;$i<$end;$i++){
            $numeric=0;
            $counter = 0;
            for($j=$i;$j<$i+$smooth;$j++){
                if(isset($ex[$j])){
                    $numeric = (float)$numeric+(float)$ex[$j];
                    $counter++;
                }
            }
           // if($i < 0 || $i >= $count){
            //    $list[] = "0";
           // }else{
                $list[] = $numeric/$smooth;
           // }
        }
        $imp = implode(",",$list);
        return $imp;
    }

    public function step3Graph($graphe_id = ""){
        $user_id = $this->uAuth['id'];
        $grafData = $this->GrapheDatas->find()
            ->where([
                'user_id'=>$user_id,
                'graphe_id'=>$graphe_id
            ])->order("disp >0 DESC,disp ");
        //並び順の指定
        $this->editSort($graphe_id);

        $this->set("id",$graphe_id);
        $this->set("grafData",$grafData);

    }
    public function editSort($graphe_id){
        $user_id = $this->uAuth['id'];
        $grafData = $this->GrapheDatas->find()
        ->where([
            'user_id'=>$user_id,
            'graphe_id'=>$graphe_id,
            'disp !='=>0
        ])->order("disp");
        $no = 1;
        foreach($grafData as $key=>$value){
            $GrapheDatas = $this->GrapheDatas->get($value[ 'id' ]);
            $GrapheDatas->disp = $no;
            $this->GrapheDatas->save($GrapheDatas);
            $no++;
        }
    }
    public function editSortArray($graphe_id){
        $this->autoRender = false;
        //チェックの対象データ
        $user_id = $this->uAuth['id'];
        $grafData = $this->GrapheDatas->find()
        ->select(['id'])
        ->where([
            'user_id'=>$user_id,
            'graphe_id'=>$graphe_id,
            'disp !='=>0
        ])->order("disp")->toArray();
        $chklist = [];
        foreach($grafData as $key=>$value){
            $chklist[$value['id']] = "on";
        }
        $array = $this->request->getData("array");
        $no = 1;
        foreach($array as $key=>$value){
            $ex = explode("-",$value);
            if($chklist[$ex[1]]){
                $GrapheDatas = $this->GrapheDatas->get($ex[1]);
                $GrapheDatas->disp = $no;
                $this->GrapheDatas->save($GrapheDatas);
                $no++;
            }
        }
        exit();
    }



    public function step4($graphe_id){

        $this->set("id",$graphe_id);
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
    /*
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
    */
    //csvExport
    public function outputGraphe($graphe_id){
        $this->autoRender = false;
        $array_basic[1] = "Number";
        $array_basic[2] = "Mass";
        $array_display[1] = "Count";
        $array_display[2] = "Normalized";

        preg_match("/[0-9]/",$this->request->getData("CSVExport-analyticsBasic"),$basic);
        preg_match("/[0-9]/",$this->request->getData("CSVExport-dataDisplay"),$display);
        $code = $basic[0].$display[0];
        $graf_type = $this->array_graf_type[$code];

        $SopDefaults = $this->SopDefaults->find()->where([
            "user_id"=>$this->uAuth['id'],
            "graphe_id"=>$graphe_id
            ])->first();
        //var_dump($SopDefaults);
        //exit();

        $graphe_datas = $this->GrapheDatas
        ->find('all',[
            "order"=>['disp is null','disp = 0 asc','disp asc']
        ])
        ->where([
            "user_id"=>$this->uAuth['id'],
            "graphe_id"=>$graphe_id
        ])->toArray();
        /*
        $sort = [];
        foreach($graphe_datas as $key=>$value){
            $sort[$key] = $value['disp'];
        }

        array_multisort($sort,SORT_ASC,$graphe_datas);
        */
        $row = 0;
        $list = [];
        $list[$row++][] = mb_convert_encoding('設定情報','SJIS','UTF-8');
        $list[$row][] = mb_convert_encoding('ファイル作成日','SJIS','UTF-8');
        $list[$row++][] = date("Y/m/d");
        $list[$row][] = mb_convert_encoding('グラフの開始値','SJIS','UTF-8');
        $list[$row++][] = $SopDefaults->defaultpoint;
        $list[$row][] = mb_convert_encoding('グラフの終了値','SJIS','UTF-8');
        $list[$row++][] = $SopDefaults->dispareamax;
        $list[$row][] = mb_convert_encoding('Binサイズ','SJIS','UTF-8');
        $list[$row++][] = $SopDefaults->binsize;
        $list[$row][] = mb_convert_encoding('スムージング','SJIS','UTF-8');
        $list[$row++][] = $SopDefaults->smooth;
        $list[$row][] = mb_convert_encoding('解析基準','SJIS','UTF-8');
        $list[$row++][] = $array_basic[$basic[0]];
        $list[$row][] = mb_convert_encoding('データ表示','SJIS','UTF-8');
        $list[$row++][] = $array_display[$display[0]];
        $list[$row++][] = "";
        $list[$row++][] = mb_convert_encoding('度数分布表','SJIS','UTF-8');
        $list[$row][] = mb_convert_encoding('階級(kDa)','SJIS','UTF-8');
        $list[$row][] = mb_convert_encoding('階級最小値(kDa)','SJIS','UTF-8');
        $list[$row][] = mb_convert_encoding('階級最大値(kDa)','SJIS','UTF-8');
        $list[$row][] = mb_convert_encoding('階級中央値(kDa)','SJIS','UTF-8');
        foreach($graphe_datas as $key=>$value){
            $list[$row][] = $value->label;
        }
        $row++;


        $GrapheDisplays = $this->GrapheDisplays->find('all')
        ->where(
            [
                'GrapheDisplays.graphe_id'=>$graphe_id,
                'GrapheDisplays.user_id'=>$this->uAuth['id'],
            ]
            );
/*
        if($this->request->getData("CSVExport-min_x") ){
            $GrapheDisplays = $GrapheDisplays->where([
                'GrapheDisplays.min >= '=>$this->request->getData("CSVExport-min_x")
                ]);
        }

        if($this->request->getData("CSVExport-max_x") ){
            $GrapheDisplays = $GrapheDisplays->where([
                'GrapheDisplays.max <= '=>$this->request->getData("CSVExport-max_x")
                ]);
        }

        if($this->request->getData("CSVExport-min_y") ){
            $GrapheDisplays = $GrapheDisplays->where([
                'GrapheDisplays.'.$graf_type.' >= '=>$this->request->getData("CSVExport-min_y")
                ]);
        }
        if($this->request->getData("CSVExport-max_y") ){
            $GrapheDisplays = $GrapheDisplays->where([
                'GrapheDisplays.'.$graf_type.' <= '=>$this->request->getData("CSVExport-max_y")
                ]);
        }
*/

        $GrapheDisplays = $GrapheDisplays->toArray();

        $points = [];
        $pointData = [];
        $n = 0;

        foreach($GrapheDisplays as $key=>$value){
            $pointData[$value->graphe_data_id][$n] = $value->$graf_type;
            $points[$value->graphe_data_id][$n][ 'min' ] = $value->min;
            $points[$value->graphe_data_id][$n][ 'max' ] = $value->max;
            $points[$value->graphe_data_id][$n][ 'center' ] = $value->center;
            $n++;
        }
        $implodes = [];
        foreach($pointData as $key=>$value){
            $imp[ 'cnt' ] = implode(",",$value);
            $implode = $this->setSmooth($imp,$SopDefaults->smooth);
            $implodes[$key] = explode(",",$implode);
        }

        $def = $row;
        $first = true;
        foreach($graphe_datas as $key=>$value){
            $no = 0;
            foreach($points[$value->id] as $k=>$val){

                if($first){
                    $list[$row][] = $val['min'];
                    $list[$row][] = $val['min'];
                    $list[$row][] = $val['max'];
                    $list[$row][] = $val['center'];
                }
                //$list[$row][] = $val['count'];
               // if(isset($implodes[$value->id][$no])){
                    $list[$row][] = $implodes[$value->id][$no];
               // }else{
               //     $list[$row][] = "-";
               // }
                $row++;
                $no++;
            }
            $row=$def;
            $first = false;
        }




        //保存場所
        $filename = "Graph-CSV-".date('YmdHis') . '.csv';
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
        $filename = "Measurement-".date('YmdHis') . '.csv';
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
        $this->autoRender=false;
        $GrapheDatas = $this->GrapheDatas->get($id, [
            'contain' => [],
        ]);
        $GrapheDatas = $this->GrapheDatas->patchEntity($GrapheDatas, $this->request->getData());
        $this->GrapheDatas->save($GrapheDatas);
    }
    public function editDispStatus($id)
    {
        $this->autoRender = false;
        $this->GrapheDatas->updateAll(['disp' => '0'], ['graphe_id' => $id]);
        $sort = 1;
        foreach($this->request->getData("graph_status") as $k=>$value){
            if($value == "on"){
                $GrapheDatas = $this->GrapheDatas->get($k);
                $GrapheDatas->disp = $sort;
                $this->GrapheDatas->save($GrapheDatas);
                $sort++;
            }
        }


        $this->Flash->success(__('データの更新を行いました。'));

        return $this->redirect(['controller'=>"Graphs",'action' =>'step3',$id]);

        exit();
    }
    public function editDispSmooth($id)
    {
        $this->autoRender = false;
        $userid = $this->uAuth['id'];
        $smooth = $this->request->getData("smooth");
        $SopDefaults = $this->SopDefaults->find()->where([
            "graphe_id"=>$id,
            'user_id'=>$userid
        ])->first();
        $SopDefaults->smooth = $smooth;
        $this->SopDefaults->save($SopDefaults);
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
            $set[ 'smooth' ] = 1;
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

    //エリア毎のテーブル表示
    public function getAreaTable($id){
        $this->autoRender = false;
        $connection = ConnectionManager::get('default');

        $areas= $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$id,
        //    "minpoint != "=>0,
        //    "maxpoint != "=>0
        ])->toArray();

        $user_id = $this->uAuth['id'];
        $SopAreas[ 'areas' ] = $areas;

        $SopDefaults = $this->SopDefaults->find()->where([
            "user_id"=>$this->uAuth['id'],
            "graphe_id"=>$id
        ])->first();


        $min = $SopDefaults[ 'defaultpoint' ];
        $max = $SopDefaults[ 'dispareamax' ];

        preg_match("/[0-9]/",$this->request->getData("basic"),$basic);
        preg_match("/[0-9]/",$this->request->getData("display"),$display);

        $code = $basic[0].$display[0];
        $clum = $this->array_graf_type[$code];
        $smooth = $SopDefaults[ 'smooth' ];

        $sql = "
            SELECT a.* FROM (
            SELECT ";
            foreach($areas as $k=>$value){
                $sql .= " SUM( CASE WHEN disp.min >= ".$value[ 'minpoint' ]." AND disp.min < ".$value[ 'maxpoint' ]." THEN disp.".$clum." ELSE 0 END ) AS lot_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";

                $sql .= " SUM(  disp.".$clum."  ) AS total_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";
            }
        $sql .= "
                graphe_data_id,
                data.counts as total,
                data.label as label,
                data.disp as disp
            FROM
                graphe_displays as disp
                LEFT JOIN graphe_datas as data ON data.id  = disp.graphe_data_id
            where
                disp.user_id = ${user_id} AND
                disp.graphe_id = ${id}
                GROUP BY disp.graphe_data_id
            ) as a
            ORDER BY a.disp > 0 DESC , a.disp , a.label
        ";

        $list = $connection->execute($sql)->fetchall('assoc');

        $sql = "
                SELECT
                ";
                foreach($areas as $k=>$value){
                    $sql .= " a.pt_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ]."/a.c_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ]." as avg_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";
                }
        $sql .= "a.id ";
        $sql .= "
                FROM (
                SELECT ";
                   foreach($areas as $k=>$value){
                        $sql .= " SUM( CASE WHEN  pt.pointdata >= ".$value[ 'minpoint' ]." AND pt.pointdata < ".$value[ 'maxpoint' ]." THEN pt.pointdata ELSE 0 END ) AS pt_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";

                        $sql .= " SUM( CASE WHEN  pt.pointdata >= ".$value[ 'minpoint' ]." AND pt.pointdata < ".$value[ 'maxpoint' ]." THEN 1 ELSE 0 END ) AS c_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ].",";

                    }

        $sql .= "
                pt.id
                FROM
                    graphe_points as pt
                    LEFT JOIN graphe_datas as data ON data.id  = pt.graphe_data_id
                WHERE
                    pt.user_id = ${user_id} AND
                    pt.graphe_id = ${id}

                    GROUP BY pt.graphe_data_id
                    ORDER BY data.disp ASC
                ) as a
        ";
       // $this->log($sql, LOG_DEBUG);
        $points = $connection->execute($sql)->fetchall('assoc');
       // $this->log($points, LOG_DEBUG);
        //中央値の取得
        $median = [];
        foreach($areas as $k=>$value){
            $minpoint = $value['minpoint'];
            $maxpoint = $value['maxpoint'];
            $sql = "
                    SELECT
                        *
                    FROM
                        graphe_points
                    WHERE
                        user_id = ${user_id} AND
                        graphe_id = ${id} AND
                        pointdata >= ${minpoint} AND
                        pointdata < ${maxpoint} ";
            $medi = $connection->execute($sql)->fetchall('assoc');
            $ex = [];
            $pt = "m_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ];
            foreach($medi as $ky=>$val){
                $ex[ $pt ][$val[ 'graphe_data_id' ]][] = $val[ 'pointdata' ];
            }
            if(!empty($ex[ $pt ])){
                foreach($ex[$pt] as $ky=>$val){
                    $median[ $pt ][$ky] = $this->median($val);
                }
            }
        }

        //モード値の取得
        $mode = [];
        if($basic[0] == 2){
            $clum = "counts2";
        }else{
            $clum = "counts1";
        }

        foreach($areas as $k=>$value){
            $minpoint = $value['minpoint'];
            $maxpoint = $value['maxpoint'];
            $sql = "
                    SELECT
                        *
                    FROM
                        graphe_displays
                    WHERE
                        user_id = ${user_id} AND
                        graphe_id = ${id} AND
                        min >= ${minpoint} AND
                        max < ${maxpoint} ";

            $mod = $connection->execute($sql)->fetchall('assoc');
            $ex = [];
            $pt = "m_".$value[ 'id' ]."_".$value[ 'minpoint' ]."_".$value[ 'maxpoint' ];
            $center = [];
            foreach($mod as $ky=>$val){
                $ex[ $pt ][$val[ 'graphe_data_id' ]][] = $val[ $clum ];
                if(empty($center[$val[ 'graphe_data_id' ]][$val[ $clum ]])){
                    $center[$val[ 'graphe_data_id' ]][$val[ $clum ]] = $val['center'];
                }
            }

            if(!empty($ex[$pt])){
                foreach($ex[$pt] as $ky=>$val){
                    $mode[ $pt ][$ky] = $center[$ky][$this->mode($val)];
                }
            }
        }
        $lists = [];
        $label = [];
        $lot = 0;
        $ave = 0;
        foreach($list as $key=>$value){
            $total = $value['total'];
            $label[$key]['label'] = $value[ 'label' ];
            $no = 0;
            foreach($areas as $k=>$val){
                $lot = "lot_".$val[ 'id' ]."_".$val[ 'minpoint' ]."_".$val[ 'maxpoint' ];
                $avg = "avg_".$val[ 'id' ]."_".$val[ 'minpoint' ]."_".$val[ 'maxpoint' ];
                $tl = "total_".$val[ 'id' ]."_".$val[ 'minpoint' ]."_".$val[ 'maxpoint' ];
                $m = "m_".$val[ 'id' ]."_".$val[ 'minpoint' ]."_".$val[ 'maxpoint' ];

                $cals = $value[$lot]/$value[$tl];

                $lists[$key][$no][ 'lot' ] = round(($cals)*100,2);
                $lists[$key][$no][ 'ave' ] = round($points[$key][$avg],2);
                $lists[$key][$no][ 'bunshi' ] = $value[$lot];

                if(isset($median[$m][$value[ 'graphe_data_id' ]])){
                    $lists[$key][$no][ 'median' ] = round($median[$m][$value[ 'graphe_data_id' ]],2);
                }else{
                    $lists[$key][$no][ 'median' ] = "0";
                }
                if(isset($mode[$m][$value[ 'graphe_data_id' ]])){
                    $lists[$key][$no][ 'mode' ] = $mode[$m][$value[ 'graphe_data_id' ]];
                }else{
                    $lists[$key][$no][ 'mode' ] = "0";
                }
                $no++;
            }
        }
        $SopAreas[ 'label' ] = $label;
        $SopAreas[ 'lists' ] = $lists;

        if($this->request->getData( 'exflag' ) == "export"){
            $this->tableDataExport($id,$SopAreas);
            exit();
        }
        header('Content-type: application/json');
        echo json_encode($SopAreas,JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function tableDataExport($graphe_id="",$sa=[]){
        $alphabet = [
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'
        ];
        // 入出力の情報設定
        $driPath    = realpath(TMP) . "/excel/";
        $inputPath  = $driPath . "templete2.xlsx";
        $sheetName  = "data_sheet";
        $temp  = "temp";
        $outputFile = "output_" . $graphe_id . ".xlsx";
        $outputPath = $driPath . $outputFile;

        // Excalファイル作成
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $book  = $reader->load($inputPath);
        $sheet  = $book->getSheetByName($sheetName);
        $tmpsheet  = $book->getSheetByName($temp);
        $lists = $sa['lists'];
        $label = $sa['label'];

        // データを配置
        $sheet->setCellValue("E2",$sa['areas'][0][ 'minpoint' ]);
        $sheet->setCellValue("G2",$sa['areas'][0][ 'maxpoint' ]);
        $sheet->setCellValue("J2",$sa['areas'][1][ 'minpoint' ]);
        $sheet->setCellValue("L2",$sa['areas'][1][ 'maxpoint' ]);
        $sheet->setCellValue("O2",$sa['areas'][2][ 'minpoint' ]);
        $sheet->setCellValue("Q2",$sa['areas'][2][ 'maxpoint' ]);
        $sheet->setCellValue("T2",$sa['areas'][3][ 'minpoint' ]);
        $sheet->setCellValue("V2",$sa['areas'][3][ 'maxpoint' ]);
        $sheet->setCellValue("Y2",$sa['areas'][4][ 'minpoint' ]);
        $sheet->setCellValue("AA2",$sa['areas'][4][ 'maxpoint' ]);
        $row = 4;
        $num = 1;

        foreach($lists as $key=>$value){
            $sheet->setCellValue("A".$row,$num);
            $sheet->setCellValue("B".$row,$label[$key]['label']);
            $a = 2;
            foreach($value as $k=>$val){
                $sheet->setCellValue($alphabet[$a++].$row,$val[ 'bunshi' ]);
                $sheet->setCellValue($alphabet[$a++].$row,$val[ 'lot' ]);
                $sheet->setCellValue($alphabet[$a++].$row,$val[ 'ave' ]);
                $sheet->setCellValue($alphabet[$a++].$row,$val[ 'median' ]);
                $sheet->setCellValue($alphabet[$a++].$row,$val[ 'mode' ]);
            }


            //スタイル
            for($i=0;$i<27;$i++){
                $sheet
                ->getStyle($alphabet[$i].$row)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                // 色を指定する場合
                $sheet
                    ->getStyle($alphabet[$i].$row)
                    ->applyFromArray([
                        'borders' => [
                            'allborders' => [
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                ]);
            }
            $row++;
            $num++;
        }

        // 保存
        $book->setActiveSheetIndex(0);
        $writer = PHPExcel_IOFactory::createWriter($book, 'Excel2007');
        $writer->save($outputPath);


        exit();
    }
    public function tabledataoutput($id=""){
        $this->autoRender=false;
        $filepath = TMP.'excel/output_'.$id.".xlsx";
        // リネーム後のファイル名
        $filename = "Table-".date('Ymdhis').'.xlsx';
        // ファイルタイプを指定
        header('Content-Type: application/force-download');
        // ファイルサイズを取得し、ダウンロードの進捗を表示
        header('Content-Length: '.filesize($filepath));
        // ファイルのダウンロード、リネームを指示
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        // ファイルを読み込みダウンロードを実行
        readfile($filepath);
        exit();
    }

    public function createDispGraph($id = ""){
        $this->autoRender = false;
        $connection = ConnectionManager::get('default');
        $user_id = $this->uAuth['id'];


        preg_match("/[0-9]/",$this->request->getData("basic"),$basic);
        preg_match("/[0-9]/",$this->request->getData("display"),$display);

        $code = $basic[0].$display[0];

/*
        $areas= $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$id
        ])->toArray();
*/

        $SopDefaults = $this->SopDefaults->find()->where([
            "user_id"=>$this->uAuth['id'],
            "graphe_id"=>$id
        ])->first();

        $clum = $this->array_graf_type[$code];
        $sql = "
            SELECT a.* FROM (
            SELECT
            ";
               // GROUP_CONCAT( ".$clum." order by gdisplay.min ) as cnt,
        $sql .= "
                gdisplay.graphe_data_id,
                gdata.label,
                gdata.disp
            FROM
                graphe_displays as gdisplay
                LEFT JOIN graphe_datas as gdata ON gdisplay.graphe_data_id = gdata.id
            WHERE
                gdisplay.user_id='${user_id}' AND
                gdisplay.graphe_id = '${id}' AND
                gdata.disp != 0 ";




            $sql .= " GROUP BY gdisplay.graphe_data_id
                ORDER BY gdisplay.min asc
                ) as a
            ORDER BY a.disp ASC
            ";

        $lists = $connection->execute($sql)->fetchall('assoc');
        $list = [];
        foreach($lists as $value){
            $list[$value[ 'graphe_data_id' ]] = $value;
        }


        $sql = "
            SELECT ".$clum." as cnt ,graphe_data_id FROM graphe_displays  WHERE user_id='${user_id}' AND  graphe_id = '${id}'
        ";
        if($this->request->getData("min_x") && $this->request->getData("max_x")){
            $sql .= " AND max >= ".$this->request->getData('min_x');
            $sql .= " AND min <= ".$this->request->getData('max_x');
        }
        $disp = $connection->execute($sql)->fetchall('assoc');

        $lines = [];
        $line = [];
        foreach($disp as $key=>$value){
            $lines[$value[ 'graphe_data_id' ]][] = $value[ 'cnt' ];
        }
        foreach($lines as $key=>$value){
            $line[$key] = implode(",",$value);
        }
        foreach($list as $key=>$value){
            $list[$key][ 'cnt' ] = $line[$key];
        }

        $smooth = $SopDefaults[ 'smooth' ];


        foreach($list as $key=>$value){
            $list[$key][ 'cnt' ] = $this->setSmooth($value,$smooth);
        }

        $display['list'] = $list;



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
    	$max = max($values);//配列から最大値を取得する。
    	return $max;
    }
    /*
    *中央値を求める関数
    */
    public function median(array $val){

        $values = [];
        foreach($val as $key=>$value){
            if($value) $values[] = $value;
        }
		sort($values);
		if (count($values) % 2 == 0){
			return (($values[(count($values)/2)-1]+$values[((count($values)/2))])/2);
		}else{
			return ($values[floor(count($values)/2)]);
		}
	}

    public function outputSOP($graphe_id){
        $user_id = $this->uAuth['id'];
        $sopDefaults = $this->SopDefaults->find()->where([
            'user_id'=>$user_id,
            'graphe_id'=>$graphe_id
        ])->first();

        $SopAreas = $this->SopAreas->find()->where([
            "user_id"=>$this->uAuth[ 'id' ],
            "graphe_id"=>$graphe_id,
        ])->order(['id'=>"ASC"])->toArray();

        $defaultpoint = $sopDefaults[ 'defaultpoint' ];
        $dispareamax  = $sopDefaults[ 'dispareamax' ];
        $binsize      = $sopDefaults[ 'binsize' ];
        $smooth       = $sopDefaults[ 'smooth' ];

        $list = [];
        $row = 0;
        $lists[$row][] = mb_convert_encoding('グラフの初期値','SJIS','UTF-8');
        $lists[$row++][] = $defaultpoint;
        $lists[$row][] = mb_convert_encoding('表示範囲','SJIS','UTF-8');
        $lists[$row++][] = $dispareamax;
        $lists[$row][] = mb_convert_encoding('Binサイズ（間隔）','SJIS','UTF-8');
        $lists[$row++][] = $binsize;
        $lists[$row][] = mb_convert_encoding('スムージング','SJIS','UTF-8');
        $lists[$row++][] = $smooth;

        $lists[$row++][] = mb_convert_encoding(' ','SJIS','UTF-8');
        $lists[$row][] = mb_convert_encoding('','SJIS','UTF-8');
        $lists[$row][] = mb_convert_encoding('下限','SJIS','UTF-8');
        $lists[$row++][] = mb_convert_encoding('上限','SJIS','UTF-8');

        $no=0;
        for($i=0;$i<=4;$i++){
            if($i == 0){
                $lists[$row][] = mb_convert_encoding('全範囲','SJIS','UTF-8');
            }else{
                $lists[$row][] = mb_convert_encoding('エリア'.$i,'SJIS','UTF-8');
            }
            $lists[$row][] = $SopAreas[$no][ 'minpoint' ];
            $lists[$row++][] = $SopAreas[$no][ 'maxpoint' ];
            $no++;
        }



        //保存場所
        $filename = "SOP-".date('YmdHis') . '.csv';
        $file = WWW_ROOT.'csv/' .$filename;
        $f = fopen($file, 'w');
        foreach($lists as $key=>$list){
            fputcsv($f, $list);
        }
        fclose($f);
        return $this->response->withFile(
            $file,
            [
                'download'=>true,
            ]
            );

        exit();
    }
}
