import { reactive } from "vue";

global.timequeque = null;

global.Queque = reactive({
    run: false,
    name: '',
    state: '',
    signal: null,
    download: false,
    stop: false,
});

global.doQueque = function (responseData) {

    if(SWorker){
        SWorker.QuequeOn(responseData);
        return;
    }

    Queque.run = true;
    Queque.name = responseData.name;
    clearTimeout(timequeque);
    Queque.signal = responseData.signal;

    if (responseData.ready) {
        
        if(responseData.has_err){
            msg(Queque.name + ' hiba!', 'error', null, responseData.has_err);
        }else{
            tMsg.s(Queque.name + ' befejeződött');
        }
        
        if(responseData.content && responseData.content.download){
            Queque.download = responseData.content.download;
        }else{
            Queque.run = false;
            Queque.name = '';
        }

    } else {
        timequeque = setTimeout((x) => {
            getStore ('admin.quedownload').list('hide',{
                modul_azon: getActiveModul(),
                stop: Queque.stop,
            });
        }, 5000);
    }
}