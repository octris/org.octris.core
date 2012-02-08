#!/usr/bin/env php
<?php

/**
 * Documentaton server.
 *
 * @octdoc      h:project/octdocd
 * @copyright   copyright (c) 2012 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
/**/

$sapi = php_sapi_name();
$info = posix_getpwuid(posix_getuid());
$home = $info['dir'] . '/.octdoc';

if ($sapi == 'cli') {
    // test php version
    $version = '5.4.0RC7';

    if (version_compare(PHP_VERSION, $version) < 0) {
        die(sprintf("unable to start webserver. please upgrade to PHP version >= '%s'. your version is '%s'\n", $version, PHP_VERSION));
    }

    // create working directory
    if (!is_dir($home)) {
        mkdir($home, 0755);
    }

    // leave, if octdocd server is already running
    exec('ps ax | grep octdocd.php | grep -v grep | grep "\-S"', $out, $ret);

    if ($ret === 0) {
        die("octdocd is already running\n");
    }

    // restart octdocd using php's webserver
    $cmd    = exec('which php', $out, $ret);
    $router = __FILE__;

    if ($ret !== 0) {
        die("unable to locate 'php' in path\n");
    }

    $host = '127.0.0.1';
    $port = '8888';

    exec(sprintf('((%s -d output_buffering=on -S %s:%s %s 1>/dev/null 2>&1 &) &)', $cmd, $host, $port, $router), $out, $ret);
    exec('ps ax | grep octdocd.php | grep -v grep | grep "\-S"', $out, $ret);

    if ($ret !== 0) {
        die(sprintf("unable to start webserver on '%s:%s'\n", $host, $port));
    } else {
        die(sprintf("octdocd server started on '%s:%s'\n", $host, $port));
    }
} elseif ($sapi != 'cli-server') {
    die("unable to execute octdocd server in environment '$sapi'\n");
}

// remove shebang from output
ob_end_clean();

// view controller
if (isset($_POST['ACTION'])) {
    $return = array('status' => '', 'error' => '');
    $action = $_POST['ACTION'];

    switch($action) {
        case 'load':
            if (!isset($_POST['file']) || !is_file(($file = $home . '/doc/' . $_POST['file']))) {
                $return['error'] = "Unable to load '$file'";
                break;
            }

            $return['text'] = file_get_contents($file);

            break;
        case 'recreate':
        case 'poll':
            // test if documentation creator is still running
            exec('ps ax | grep doc.php | grep -v grep', $out, $ret);

            if ($ret === 1) {
                $return['status'] = 'ok';
            }

            if ($action == 'poll') {
                break;
            }

            exec(sprintf('((%s -p org.octris.core 2>/dev/null | (cd %s && tar -xpf -) &) &)', __DIR__ . '/doc.php', $home));
            break;
    }

    die(json_encode($return));
}

// render documentation browser
?>
<html>
    <head>
        <title>org.octris.core -- documentation server</title>
        <style type="text/css">
        /* generic settings */
        body {
            margin: 0 auto;
            width:  900px;

            background-color: #fff;

            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size:   0.9em;
        }
        #error {
            width:       868px;
            margin:      50px 5px -40px;
            padding:     10px;
            font-weight: bold;
            color:       darkred;
            border:      1px solid darkred;
            display:     none;
        }

        /* main container */
        #main {
            min-height:  100%;

            border-left:  1px dotted #ddd;
            border-right: 1px solid #ddd;

            background-color: #eee;
        }

        /* index */
        #index {
            display:    inline-block;
            width:      239px;

            vertical-align: top;
            margin:         30px 5px;
        }

        /* content */
        #content {
            display: inline-block;
            width:   638px;

            vertical-align: top;
            margin:         30px 5px;
        }
        #content pre {
            border:           1px solid #ccc;
            background-color: #fff;
            padding:          5px;
        }
        #content dt {
            margin-top:  20px;
            font-weight: bold;
            font-size: 1em;
        }
        #content dd {
            margin-top: 10px;
        }
        #content dd table {
            font-size: 1em;
            color:     #000;
        }
        #content dd table thead tr th {
            font-size:     1em;
            text-align:    left;
            border-bottom: 1px solid #ccc;
        }
        #content dd table tbody tr td {
            border-bottom:  1px solid #ccc;
            vertical-align: top;
        }

        /* footer */
        #footer {
            z-index:  1;

            position: fixed;
            bottom:   0;
            
            opacity:  0.9;

            background-color: #fff;
            border-top:       1px solid #ddd;
            border-left:      1px solid #ddd;
            border-right:     1px solid #ddd;

            height:           30px;
            width:           898px;

            font-size:        10px;
            line-height:      12px;
            color:            #000;

            text-align:       center;
            vertical-align:   center;
        }
        #footer a {
            color: #000;
        }

        /* toolbar */
        #toolbar {
            z-index:  1;

            position: fixed;
            top:      0;

            opacity:    0.9;

            background-color: #fff;
            border-bottom:    1px solid #ddd;
            border-left:      1px solid #ddd;
            border-right:     1px solid #ddd;
            height:           30px;
            width:           888px;

            padding:           5px;

            font-size:        22px;
            line-height:     26px;
            color:           #000;
        }
        #toolbar a {
            display:         inline-block;
            text-decoration: none;
            padding-left:    30px;
            color:           #000;
        }
        #toolbar a.search {
            background: url("data:image/png;base64,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") no-repeat left center;
        }
        #toolbar a.print {
            background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAVCAYAAABc6S4mAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJlJREFUeNpidHFxYUAD/xmIA81A7AXEJvgUMTFQDs6QawEjDkySJfgs+I8Dk+QTagQRXktYCAQRLp/BwDZCNrIQCCJ8oBaHeB0tg4iBGkFEko8pCSKiwIAEUSO1LWgA4noqmolsViMTlQ3HsIzacbAAWrouoFUkTwHis1CaJhbkALExlCaYD8gBCVBMv3xAFwsaaWh+I0CAAQA96RuXMmw65gAAAABJRU5ErkJggg==") no-repeat left center;
        }
        #toolbar a.recreate {
            background: url("data:image/png;base64,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") no-repeat left center;
        }
        #toolbar a.working {
            opacity: 0.5;
            cursor:  default;
            background: url("data:image/gif;base64,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") no-repeat left center;
        }

        /* styles for printing */
        @media print {
            #content {
                display:          block;
                border:           0;
                background-color: #fff;
            }
            #toolbar {
                display: none;
            }
            #footer {
                display: none;
            }
            #index {
                display: none;
            }
        }
        </style>
        <script type="text/javascript">
        function $(id) {
            var node = document.getElementById(id);

            return new function() {
                this.node = node;
            }
        }

        var request = (function() {
            var getRequest = (function() {
                if (window.ActiveXObject) {
                    return function() { return new ActiveXObject('Microsoft.XMLHTTP'); }
                } else if (window.XMLHttpRequest) { 
                    return function() { return new XMLHttpRequest(); }
                } else {
                    return function() { return false; }
                }
            })();

            function j2q(obj, pre) {
                var ret = [];
                var o   = (typeof obj != 'object' ? [obj] : obj);
                var k, v;

                pre = pre || '';

                if ('length' in o) {
                    for (var i = 0, len = o.length; i < len; ++i) {
                        v = o[i]; 
                        k = (pre != '' ? pre + '[' + i + ']' : i);

                        ret.push((typeof v == 'object' ? j2q(v, k) : k + '=' + encodeURIComponent(v)));
                    }
                } else {
                    for (k in o) {
                        v = o[k];
                        k = encodeURIComponent(k);
                        k = (pre != '' ? pre + '[' + k + ']' : k);

                        ret.push((v === null ? k + '=' : (typeof v == 'object' ? j2q(v, k) : k + '=' + encodeURIComponent(v))));
                    }
                }

                return ret.join('&');
            }

            return function(url, data, cb) {
                var request = getRequest();

                if (request) {
                    request.onreadystatechange = function() {
                        if (request.readyState == 4) {
                            var data  = {};
                            var error = false;

                            try {
                                data = (request.responseText != ''
                                        ? eval('(' + request.responseText + ')')
                                        : {});
                            } catch(e) {
                            }

                            if ((error = ('error' in data && data['error'] != ''))) {
                                $('error').node.style.display = 'inline-block';
                                $('error').node.innerHTML = data['error'];
                            } else {
                                $('error').node.style.display = 'none';
                            }

                            cb(data, error);
                        }
                    }

                    request.open('POST', url, true);
                    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    request.send(j2q(data));
                } else {
                    alert('Not possible with your Browser!');
                }
            }
        })();

        window.onload = (function() {
            function load(name, cb) {
                cb = cb || function() {};

                request('/', {'ACTION': 'load', 'file': name}, function(data, error) {
                    if ('text' in data) {
                        $('text').node.innerHTML = data['text'];
                    }

                    cb(data, error);
                });
            }

            var recreate = false;

            return function() {
                $('bt_recreate').node.onclick = function() {
                    if (!recreate) {
                        $('bt_recreate').node.className = 'working';
    
                        request('/', {'ACTION': 'recreate'}, function(data, error) {
                            var to = 1500;
                            var cb = function() {
                                request('/', {'ACTION': 'poll'}, function(data, error) {
                                    if (!('status' in data) || data['status'] != 'ok') {
                                        window.setTimeout(cb, to);
                                        return;
                                    }

                                    if (error) {
                                        $('bt_recreate').node.className = 'recreate';
                                        recreate = false;
                                    } else {
                                        load('index.html', function() {
                                            $('bt_recreate').node.className = 'recreate';
                                            recreate = false;
                                        });
                                    }
                                });
                            }

                            if (!error) window.setTimeout(cb, to);
                        });

                        recreate = true;
                    }
                }

                $('bt_print').node.onclick = function() {
                    window.print();
                }
            }
        })();
        </script>
    </head>
    <body>
        <div id="toolbar">
            Documentation Browser
            <div style="float: right;">
                <a id="bt_search" class="search" href="javascript://">Search</a>
                &nbsp;&nbsp;
                <a id="bt_recreate" class="recreate" href="javascript://">Recreate</a>
                &nbsp;&nbsp;
                <a id="bt_print" class="print" href="javascript://">Print</a>
            </div>
        </div>

        <div id="footer">
            Documentation Browser (c) 2012 by Harald Lapp &lt;harald@octris.org&gt;<br />
            Icons (c) by Glyphish &mdash; <a target="_blank" href="http://www.glyphish.com/">www.glyphish.com</a>
        </div>

        <div id="main">
            <div id="error">Error</div>

            <div id="index">
                <h1>Index</h1>
            </div><div id="content">
                <div id="text">
                    <h1>Basic installation</h1>

                    <h2>Instructions</h2>

                    <p>
                    </p>

                    <h2>Verification</h2>

                    <p>
                        Make sure, that all requirements below are fulfilled. Follow the instructions
                        above to install and configure the octris framework. Press the &quot;Reload&quot;
                        button below to retest the fulfillment of all requirements.
                    </p>

                    <center>
                        <table width="50%" border="0" cellspacing="5" cellpadding="0" style="font-weight: bold;">
                            <tr>
                                <td>
                                    OCTRIS_BASE
                                </td><td><?php
                                if (getenv('OCTRIS_BASE')) {
                                     print '<span style="color: darkgreen">yes</span>';
                                } else {
                                     print '<span style="color: darkred">no</span>';
                                }
                                ?></td>
                            </tr><tr>
                                <td>
                                    include_path
                                </td><td><?php
                                $incl = explode(PATH_SEPARATOR, get_include_path());

                                if (!($path = getenv('OCTRIS_BASE'))) {
                                     print '<span style="color: darkyellow">fix OCTRIS_BASE first</span>';
                                } elseif (array_search($path . '/libs', $incl) !== false) {
                                     print '<span style="color: darkgreen">yes</span>';
                                } else {
                                     print '<span style="color: darkred">no</span>';
                                }
                                ?></td>
                            </tr>
                        </table>
                        <br /><br />                
                        <button onclick="window.location.reload();">Reload</button>
                    </center>
                </div>
            </div>
        </div>
    </body>
</html>
