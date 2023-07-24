function startUserMedia(stream) {
    var input = audio_context.createMediaStreamSource(stream);
    recorder = new Recorder(input);
}

function startRecording() {
    recorder && recorder.record();
}

function stopRecording() {
    recorder && recorder.stop();
    // create WAV download link using audio data blob
    //createDownloadLink();
    uploadToServer();

    recorder.clear();
}

function uploadToServer(){
    recorder && recorder.exportWAV(function(blob) {

        var fileType = 'wav';
        var fileName =  new Date().valueOf() + '.' + fileType;
        var url = '/index/upload/uploadVoice';

        // create FormData
        var formData = new FormData();
        formData.append('name', fileName);
        formData.append('file', blob);

        var request = new XMLHttpRequest();
        // upload success callback
        request.onreadystatechange = function() {
            if (request.readyState == 4 && request.status == 200) {

                var reObj = eval('(' + request.response + ')');
                sendMessage('audio[' + reObj.data.src + ']');
            }
        };
        // upload start callback
        request.upload.onloadstart = function() {
            console.log('Upload started...');
        };
        // upload process callback
        request.upload.onprogress = function(event) {
            console.log('Upload Progress ' + Math.round(event.loaded / event.total * 100) + "%");
        };
        // upload error callback
        request.upload.onerror = function(error) {
            console.error('XMLHttpRequest failed', error);
        };
        // upload abort callback
        request.upload.onabort = function(error) {
            console.error('XMLHttpRequest aborted', error);
        };

        request.open('POST', url);
        request.send(formData);
    });
}
