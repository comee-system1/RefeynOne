/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./webroot/js/dropimage.js":
/*!*********************************!*\
  !*** ./webroot/js/dropimage.js ***!
  \*********************************/
/***/ (() => {

eval("// ドラッグ&ドロップエリアの取得\nvar fileArea = document.getElementById('dropArea');\n// input[type=file]の取得\nvar fileInput = document.getElementById('uploadFile');\ntry{\n    // ドラッグオーバー時の処理\n    fileArea.addEventListener('dragover', function(e){\n        e.preventDefault();\n        fileArea.classList.add('dragover');\n    });\n\n    // ドラッグアウト時の処理\n    fileArea.addEventListener('dragleave', function(e){\n        e.preventDefault();\n        fileArea.classList.remove('dragover');\n    });\n\n    // ドロップ時の処理\n    fileArea.addEventListener('drop', function(e){\n        e.preventDefault();\n        fileArea.classList.remove('dragover');\n\n        // ドロップしたファイルの取得\n        var files = e.dataTransfer.files;\n\n        // 取得したファイルをinput[type=file]へ\n        fileInput.files = files;\n        if(typeof files[0] !== 'undefined' && files[0].name.indexOf('.csv') !== -1 ) {\n            //ファイルが正常に受け取れた際の処理\n            $(\"#filename\").text(files[0]['name']+\"を選択しました。\");\n            $(this).fileupload();\n\n        } else {\n            //ファイルが受け取れなかった際の処理\n            $(\"#filename\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    });\n\n    // input[type=file]に変更があれば実行\n    // もちろんドロップ以外でも発火します\n    fileInput.addEventListener('change', function(e){\n\n        var file = e.target.files[0];\n        if(typeof e.target.files[0] !== 'undefined'  && file.name.indexOf('.csv') !== -1  ) {\n            // ファイルが正常に受け取れた際の処理\n            $(\"#filename\").text(file['name']+\"を選択しました。\");\n\n            $(this).fileupload();\n\n        } else {\n            // ファイルが受け取れなかった際の処理\n            $(\"#filename\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    }, false);\n}catch(e){}\n\n\n$.fn.fileupload = function(){\n    $(\"#screen\").show();\n    let _upfile = $('input[name=\"uploadFile\"]');\n    let fd = new FormData();\n    fd.append(\"upfile\", _upfile.prop('files')[0]);\n\n    var _id = $(\"#id\").val();\n    $.ajax({\n        url:\"/graphs/upload/\"+_id,\n        type:\"post\",\n        data:fd,\n        processData:false,\n        contentType:false,\n        cache:false,\n    }).done(function(data){\n        if(data >= 1 ){\n            alert(\"ファイルのアップロードに失敗しました\");\n        }else{\n            $(\"#screen\").hide();\n            alert(\"ファイルのアップロードを行いました。\");\n            $(this).getGraphData();\n        }\n        console.log(data);\n\n    }).fail(function(){\n\n    });\n};\n\n\n\n//////////////////////////\n\n// ドラッグ&ドロップエリアの取得\nvar fileArea2 = document.getElementById('dropArea2');\n// input[type=file]の取得\nvar fileInput2 = document.getElementById('uploadFile2');\ntry{\n    // ドラッグオーバー時の処理\n    fileArea2.addEventListener('dragover', function(e){\n        e.preventDefault();\n        fileArea2.classList.add('dragover');\n    });\n\n    // ドラッグアウト時の処理\n    fileArea2.addEventListener('dragleave', function(e){\n        e.preventDefault();\n        fileArea2.classList.remove('dragover');\n    });\n\n    // ドロップ時の処理\n    fileArea2.addEventListener('drop', function(e){\n        e.preventDefault();\n        fileArea2.classList.remove('dragover');\n\n        // ドロップしたファイルの取得\n        var files = e.dataTransfer.files;\n\n        // 取得したファイルをinput[type=file]へ\n        fileInput2.files = files;\n\n        if(typeof files[0] !== 'undefined') {\n            //ファイルが正常に受け取れた際の処理\n            $(\"#filename2\").text(files[0]['name']+\"を選択しました。\");\n            $(this).fileupload2();\n        } else {\n            //ファイルが受け取れなかった際の処理\n            $(\"#filename2\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    });\n\n    // input[type=file]に変更があれば実行\n    // もちろんドロップ以外でも発火します\n    fileInput2.addEventListener('change', function(e){\n        var file = e.target.files[0];\n\n        if(typeof e.target.files[0] !== 'undefined') {\n            // ファイルが正常に受け取れた際の処理\n            $(\"#filename2\").text(file['name']+\"を選択しました。\");\n            $(this).fileupload2();\n        } else {\n            // ファイルが受け取れなかった際の処理\n            $(\"#filename2\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    }, false);\n}catch(e){}\n\n$.fn.fileupload2 = function(){\n    $(\"#screen\").show();\n    let _upfile = $('input[name=\"uploadFile2\"]');\n    let fd = new FormData();\n    fd.append(\"upfile\", _upfile.prop('files')[0]);\n    var _id = $(\"#id\").val();\n    $.ajax({\n        url:\"/graphs/upload/\"+_id+\"/mesurement\",\n        type:\"post\",\n        data:fd,\n        processData:false,\n        contentType:false,\n        cache:false,\n    }).done(function(data){\n        console.log(data);\n        if(data >= 1 ){\n            alert(\"ファイルのアップロードに失敗しました\");\n        }else{\n            $(\"#screen\").hide();\n            alert(\"ファイルのアップロードを行いました。\");\n            $(this).getGraphData();\n        }\n\n    }).fail(function(){\n\n    });\n};\n\n\n\n\n/////////////////////////////////////\n\n\n\n// ドラッグ&ドロップエリアの取得\nvar fileArea3 = document.getElementById('dropArea3');\n// input[type=file]の取得\nvar fileInput3 = document.getElementById('uploadFile3');\ntry{\n    // ドラッグオーバー時の処理\n    fileArea3.addEventListener('dragover', function(e){\n        e.preventDefault();\n        fileArea3.classList.add('dragover');\n    });\n\n    // ドラッグアウト時の処理\n    fileArea3.addEventListener('dragleave', function(e){\n        e.preventDefault();\n        fileArea3.classList.remove('dragover');\n    });\n\n    // ドロップ時の処理\n    fileArea3.addEventListener('drop', function(e){\n        e.preventDefault();\n        fileArea3.classList.remove('dragover');\n\n        // ドロップしたファイルの取得\n        var files = e.dataTransfer.files;\n\n        // 取得したファイルをinput[type=file]へ\n        fileInput3.files = files;\n        if(typeof files[0] !== 'undefined' && files[0].name.indexOf('.csv') !== -1 ) {\n            //ファイルが正常に受け取れた際の処理\n            $(\"#filename3\").text(files[0]['name']+\"を選択しました。\");\n            $(this).fileupload3();\n\n        } else {\n            //ファイルが受け取れなかった際の処理\n            $(\"#filename3\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    });\n\n    // input[type=file]に変更があれば実行\n    // もちろんドロップ以外でも発火します\n    fileInput3.addEventListener('change', function(e){\n\n        var file = e.target.files[0];\n        if(typeof e.target.files[0] !== 'undefined'  && file.name.indexOf('.csv') !== -1  ) {\n            // ファイルが正常に受け取れた際の処理\n            $(\"#filename3\").text(file['name']+\"を選択しました。\");\n\n            $(this).fileupload3();\n\n        } else {\n            // ファイルが受け取れなかった際の処理\n            $(\"#filename3\").text(\"ファイルの選択に失敗しました。\");\n\n        }\n    }, false);\n}catch(e){}\n\n\n$.fn.fileupload3 = function(){\n    $(\"#screen\").show();\n    let _upfile = $('input[name=\"uploadFile3\"]');\n\n    let fd = new FormData();\n    fd.append(\"upfile\", _upfile.prop('files')[0]);\n    var _id = $(\"#id\").val();\n    $.ajax({\n        url:\"/graphs/upload/\"+_id+\"/sop\",\n        type:\"post\",\n        data:fd,\n        processData:false,\n        contentType:false,\n        cache:false,\n    }).done(function(data){\n\n        if(data >= 1 ){\n            alert(\"ファイルのアップロードに失敗しました\");\n        }else{\n            $(\"#screen\").hide();\n            alert(\"ファイルのアップロードを行いました。\");\n        }\n        var _url = location.href;\n        location.href = _url;\n\n    }).fail(function(){\n\n    });\n};\n\n\n\n//////////////////////////\n\n\n\n\n//# sourceURL=webpack://myapp/./webroot/js/dropimage.js?");

/***/ }),

/***/ "./webroot/js/graph.js":
/*!*****************************!*\
  !*** ./webroot/js/graph.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"write3\": () => (/* binding */ write3)\n/* harmony export */ });\nconst write3 = function () {\n\n};\n$(function(){\n    $(this).getGraphData();\n\n    //ファイル削除\n    $(document).on(\"click\",\".grapdelete\",function(){\n        if(confirm(\"取込みファイルの削除を行います。よろしいですか?\")){\n            var _graph_id = $(\"#id\").val();\n            var _graph_data_id = $(this).attr(\"id\").split(\"-\")[1];\n            location.href=\"/graphs/delete/\"+_graph_id+\"/\"+_graph_data_id;\n            return true;\n        }\n        return false;\n    });\n    //label名変更\n    $(document).on(\"blur\",\".editlabel\",function(){\n        var _id = $(this).attr(\"id\").split(\"-\")[1];\n        var _val = $(this).val();\n        var _data = {\"label\":_val};\n        $.ajax({\n            url:\"/graphs/edit/\"+_id,\n            type:\"post\",\n            data:_data,\n            datatype: \"json\",\n        }).done(function(data){\n            console.log(\"ddd\");\n        }).fail(function(){\n\n        });\n        return false;\n    });\n\n});\n$.fn.getGraphData = function(){\n\n    try{\n        var _id = $(\"#id\").val();\n        $.ajax({\n            url:\"/graphs/graphdata/\"+_id,\n            type:\"post\",\n            datatype: \"json\",\n        }).done(function(jsonstr){\n            //console.log(jsonstr);\n            var _tbl = \"\";\n\n        //  var data = $.parseJSON(jsonstr);\n            var data = jsonstr;\n            var _num = 1;\n            $(\"#tbody\").html(\"\");\n            $.each(data, function(key, value){\n                _tbl = \"<tr>\";\n                _tbl += \"<td>\"+_num,+\"</td>\";\n                _tbl += \"<td><input type='text' class='form-control editlabel' id='label-\"+value.id+\"' value='\"+value.label+\"' /></td>\";\n                _tbl += \"<td>\"+value.filename+\"</td>\";\n                _tbl += \"<td class='text-right'>\"+value.counts+\"</td>\";\n                _tbl += \"<td class='text-center'><button class='btn-sm btn-danger grapdelete' id='delete-\"+value.id+\"'>削除</button></td>\";\n                _tbl += \"</tr>\";\n                $(\"#tbody\").append(_tbl);\n                _num += 1;\n            });\n\n        }).fail(function(){\n\n        });\n    }catch(e){\n\n    }\n\n};\n\n\n//# sourceURL=webpack://myapp/./webroot/js/graph.js?");

/***/ }),

/***/ "./webroot/js/hello1.js":
/*!******************************!*\
  !*** ./webroot/js/hello1.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"write1\": () => (/* binding */ write1)\n/* harmony export */ });\nconst write1 = function () {\n    console.log(\"hello world123444\");\n};\n$(function(){\n\n\n\n\n});\n\n\n//# sourceURL=webpack://myapp/./webroot/js/hello1.js?");

/***/ }),

/***/ "./webroot/js/hello2.js":
/*!******************************!*\
  !*** ./webroot/js/hello2.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _hello1_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./hello1.js */ \"./webroot/js/hello1.js\");\n/* harmony import */ var _dropimage_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./dropimage.js */ \"./webroot/js/dropimage.js\");\n/* harmony import */ var _dropimage_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_dropimage_js__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _graph_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./graph.js */ \"./webroot/js/graph.js\");\n\n\n\n\n(0,_hello1_js__WEBPACK_IMPORTED_MODULE_0__.write1)();\n(0,_graph_js__WEBPACK_IMPORTED_MODULE_2__.write3)();\n\n\n\n$(\"#finishbutton\").click(function(){\n    if(confirm(\"終了後、データを削除します。解析情報を残すには「取込データ出力」と「SOP設定出力」、「エリア毎の結果テーブル出力」をしてから終了してください。\")){\n        return true;\n    }\n    return false;\n});\n\n\n//解析ページのselectbox\n//Bootstrap Duallistbox\n$('.duallistbox').bootstrapDualListbox({\n    filterTextClear:'全件表示',\n    filterPlaceHolder:'検索',\n    moveSelectedLabel:'選択済みに移動',\n    moveAllLabel:'選択済みに全て移動',\n    removeSelectedLabel:'選択を解除',\n    removeAllLabel:'選択を全て解除',\n    moveOnSelect: true,\n    nonSelectedListLabel: '取り込まれたデータ',\n    selectedListLabel: '表示させるデータ',\n    infoText:'{0}件',\n    showFilterInputs:false,\n    infoTextEmpty:'0件',\n    infoTextFiltered:'{1}件中{0}件表示',\n    selectorMinimalHeight:400\n});\n\n$(\"#sortable\").sortable({\n    update: function(){\n        console.log($('#sortable').sortable(\"toArray\"));\n    },\n    axis: 'y',\n});\n$(\".graph_status_edit\").on(\"click\",function(){\n    console.log(\"click\");\n});\n\n$(\"#pngExport\").on(\"click\",function(){\n    let canvas = document.getElementById('lineChart');\n    let png = canvas.toDataURL();\n    let link = document.createElement(\"a\");\n    link.href = canvas.toDataURL(\"image/png\");\n    var date = new Date() ;\n    link.download = date.getTime()+\".png\";\n    link.click();\n\n    return false;\n});\n//-------------\n//- LINE CHART -\n//--------------\nvar _pt = 100;\nvar areaChartData = {\n\n    labels  : [\n        '0', '100', '200', '300', '400', '500', '600','700','800','900',\n        '1000', '1100', '1200', '1300', '1400', '1500', '1600','1700','1800','1900',\n        '2000', '2100', '2200', '2300', '2400', '2500', '2600','2700','2800','2900',\n    ],\n\n    datasets: [\n    {\n\n      label               : 'Digital Goods',\n      backgroundColor     : 'rgba(60,141,188,0.9)',\n      borderColor         : 'rgba(255,0,0,0.8)',\n      pointRadius          : false,\n      pointColor          : '#3b8bba',\n      pointStrokeColor    : 'rgba(60,141,188,1)',\n      pointHighlightFill  : '#fff',\n      pointHighlightStroke: 'rgba(60,141,188,1)',\n      lineTension: 0,\n      borderDash:[5,5],\n      data                : [\n          _pt, 48, 40, 19, 86, 27, 90,\n          28, 48, 40, 19, 86, 27, 90,\n          28, 48, 40, 19, 86, 27, 90,\n          28, 48, 40, 19, 86, 27, 90,\n        ]\n    },\n    {\n      label               : 'Digital Goods2',\n      backgroundColor     : 'rgba(255,255,255,0.9)',\n      borderColor         : 'rgba(0,0,0,0.8)',\n      pointRadius          : false,\n      pointColor          : '#3b8bba',\n      pointStrokeColor    : 'rgba(60,141,188,1)',\n      pointHighlightFill  : '#fff',\n      pointHighlightStroke: 'rgba(60,141,188,1)',\n      lineTension: 0,\n      borderDouble:[5,5],\n      data                : [\n          8, 148, 140, 119, 186, 127, 190,\n          128, 148, 140, 119, 186, 127, 190,\n          128, 148, 140, 119, 186, 127, 190,\n          128, 148, 140, 119, 186, 127, 190,\n        ]\n    },\n    {\n      label               : 'Electronics',\n      backgroundColor     : 'rgba(210, 214, 222, 1)',\n      borderColor         : 'rgba(210, 214, 222, 1)',\n      pointRadius         : false,\n      pointColor          : 'rgba(210, 214, 222, 1)',\n      pointStrokeColor    : '#c1c7d1',\n      pointHighlightFill  : '#fff',\n      pointHighlightStroke: 'rgba(220,220,220,1)',\n      lineTension: 0,\n\n      data                : [\n          65, 59, 80, 81, 56, 55, 40,\n          65, 59, 80, 81, 56, 55, 40,\n          65, 59, 80, 81, 56, 55, 40,\n          65, 59, 80, 81, 56, 55, 40,\n        ]\n    },\n    {\n      label               : 'Electronics',\n      backgroundColor     : 'rgba(210, 214, 222, 1)',\n      borderColor         : 'rgba(10, 14, 22, 1)',\n      pointRadius         : false,\n      pointColor          : 'rgba(210, 214, 222, 1)',\n      pointStrokeColor    : '#c1c7d1',\n      pointHighlightFill  : '#fff',\n      pointHighlightStroke: 'rgba(220,220,220,1)',\n      lineTension: 0,\n\n      data                : [\n          100, 159, 180, 81, 156, 155, 140,\n          100, 159, 180, 81, 156, 155, 140,\n          100, 159, 180, 81, 156, 155, 140,\n          100, 159, 180, 81, 156, 155, 140,\n        ]\n    },\n  ]\n}\n\n\nvar areaChartOptions = {\n    maintainAspectRatio : false,\n    responsive : true,\n    legend: {\n      display: true\n    },\n    scales: {\n      xAxes: [{\n        gridLines : {\n          display : true,\n        },\n        ticks: {\n            min: \"0\",\n            max: \"3000\",\n            maxTicksLimit: 8,\n            minRotation: 0,\n            maxRotation: 0,\n        }\n      }],\n      yAxes: [{\n        gridLines : {\n          display : true,\n        }\n\n      }]\n    },\n\n    annotation: {\n        annotations: [\n            {\n                type: 'line', // 線を描画\n                id: 'hLine',\n                mode: 'vertical', // 線を水平に引く\n                scaleID: 'x-axis-0',\n                value: 10, // 基準となる数値\n                borderWidth: 3, // 基準線の太さ\n                borderColor: 'red'  // 基準線の色\n            },\n            {\n                type: 'line', // 線を描画\n                id: 'hLine2',\n                mode: 'vertical', // 線を水平に引く\n                scaleID: 'x-axis-0',\n                value: 20, // 基準となる数値\n                borderWidth: 3, // 基準線の太さ\n                borderColor: 'blue'  // 基準線の色\n            }\n        ]\n    },\n}\n\n\ntry{\n    var canvas = $('#lineChart').get(0);\n    var lineChartCanvas = canvas.getContext('2d');\n\n    var lineChartOptions = $.extend(true, {}, areaChartOptions);\n    var lineChartData = $.extend(true, {}, areaChartData);\n    lineChartData.datasets[0].fill = false;\n    lineChartData.datasets[1].fill = false;\n    lineChartData.datasets[2].fill = false;\n    lineChartData.datasets[3].fill = false;\n    lineChartOptions.datasetFill = false;\n\n\n\n    var lineChart = new Chart(lineChartCanvas, {\n        type: 'line',\n        data: lineChartData,\n        options: lineChartOptions\n    });\n\n}catch(e){\n\n}\n\n\n\n\n\n\n//# sourceURL=webpack://myapp/./webroot/js/hello2.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./webroot/js/hello2.js");
/******/ 	
/******/ })()
;