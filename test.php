
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
<body>

<h1>Chat <input id="alias" placeholder="name" /></h1>
<textarea style="display: block; width: 800px; height: 300px;" id="dl"></textarea>
<input type="text" id="id" style="width: 80px;">
<input type="text" name="txt" id="txt" style="width: 700px; margin-top: 25px;">
<button id="sb">send</button>

</body>
<script
  src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
  integrity="sha256-k2WSCIexGzOj3Euiig+TlR8gA0EmPjuc79OEeY5L45g="
  crossorigin="anonymous"></script>
<script>
var wsServer = 'ws://192.168.1.135:9502';
var ws = new WebSocket(wsServer);
ws.onopen = function (evt) {
    put("Connected to WebSocket server.\n");
};

ws.onclose = function (evt) {
    put("Disconnected\n");
};

ws.onmessage = function (evt) {
    put(evt.data+"\n");
};

ws.onerror = function (evt, e) {
    put("Error occured: \n" + evt.data);
};

$(document).ready(function(){
    $('#sb').click(function(){
        send($('#txt').val());
        $('#txt').val('');
    });
});

function put(txt){
    $('#dl').text($('#dl').text() + txt);
}

function send(txt){
    var id = $('#id').val();
    var arr = {
        "id": id,
        "alias": alias,
        "txt": txt
    };

    ws.send(JSON.stringify(arr));
}
</script>
</head>