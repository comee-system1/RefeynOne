
<div class="content">

    <div class="container">
        <?= $this->element("graph_step",['step'=>1]); ?>
        <?= $this->Form->create("", [
            'enctype' => 'multipart/form-data',
            'url'=>'/graphs/step2'
            ]); ?>
        <div class="row">
            <div class="col-md-12 mt-3">
                <div class="card ">
                    <div class="card-header bg-primary">
                        <?= __("RefeynOneからの出力ファイル取込") ?>
                        <i class="fas fa-question-circle yubi" data-toggle="modal" data-target="#modal-default" ></i>
                    </div>
                    <div class="card-body">

                        <div id="upFileWrap">
                            <div id="inputFile">
                                <!-- ドラッグ&ドロップエリア -->
                                <p id="dropArea">ここにファイルをドロップしてください<br>または</p>

                                <!-- 通常のinput[type=file] -->
                                <div id="inputFileWrap">
                                    <input type="file" name="uploadFile" id="uploadFile">
                                    <div id="btnInputFile"><span>ファイルを選択する</span></div>
                                    <div id="btnChangeFile"><span>ファイルを変更する</span></div>
                                </div>
                                <div id="filename">-</div>
                            </div>
                        </div>


                    </div>
                </div>

            </div>
            <div class="col-md-12 mt-3">
                <div class="card ">
                    <div class="card-header bg-info">
                        <?= __("LSSシステムからのエクスポートファイル取込") ?>
                        <i class="fas fa-question-circle yubi" data-toggle="modal" data-target="#modal-default2" ></i>
                    </div>
                    <div class="card-body">

                        <div id="upFileWrap2">
                            <div id="inputFile2">
                                <!-- ドラッグ&ドロップエリア -->
                                <p id="dropArea2">ここにファイルをドロップしてください<br>または</p>

                                <!-- 通常のinput[type=file] -->
                                <div id="inputFileWrap2">
                                    <input type="file" name="uploadFile2" id="uploadFile2">
                                    <div id="btnInputFile2"><span>ファイルを選択する</span></div>
                                    <div id="btnChangeFile2"><span>ファイルを変更する</span></div>
                                </div>
                                <div id="filename2">-</div>
                            </div>
                        </div>


                    </div>
                </div>

            </div>
        </div>
        <div class="row m-3">
            <div class="col-md-12 text-center">
            <?= $this->Form->submit("取込みデータ確認",[
                "class"=>"btn btn-primary w-100"

            ])?>
            </div>
        </div>
        <?= $this->Form->end(); ?>



        <table class="table table-bordered">
            <thead class="bg-success">
                <tr>
                <th style="width: 10px">#</th>
                <th>Label</th>
                <th>データ数</th>
                <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <tr>
                    <td>1.</td>
                    <td><input type="text" value="update soft ware" class="form-control" /></td>
                    <td>1,000</td>
                    <td class="text-center">
                        <a href="" class="btn-sm btn-danger">削除</a>
                    </td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td><input type="text" value="update soft ware" class="form-control" /></td>
                    <td>1,000</td>
                    <td class="text-center">
                        <a href="" class="btn-sm btn-danger">削除</a>
                    </td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td><input type="text" value="update soft ware" class="form-control" /></td>
                    <td>1,000</td>
                    <td class="text-center">
                        <a href="" class="btn-sm btn-danger">削除</a>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</div>


<!-- モーダル表示 -->
<div class="modal fade" id="modal-default">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p><?= __("「保存された各データフォルダ中の「eventsFitted.csv」というファイルをカーソルで持っていきます」") ?></p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
    <!-- /.modal-content -->
</div>
<div class="modal fade" id="modal-default2">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p><?= __("「以前エクスポートしたプロジェクトファイルをカーソルで持っていきます」") ?></p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
    <!-- /.modal-content -->
</div>

