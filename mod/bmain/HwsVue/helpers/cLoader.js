
import { defineAsyncComponent } from "vue";

var cLoadData = {};

global.cLoad = function (cmp_url, modul) {
    modul = modul || getActiveModul();
    if (!cmp_url.includes('.') && !cmp_url.includes('/') && getActiveMenu()) cmp_url = getActiveMenu() + '.' + cmp_url;
    return defineAsyncComponent(() => {
        // maskOn();
        return new Promise((resolve, reject) => {
            cmp_url = cmp_url.replaceAll('.', '/');
            if (cLoadData[modul + '/' + cmp_url]) return cLoadData[modul + '/' + cmp_url];
            var request = new XMLHttpRequest();
            request.open('GET', (getConfig().remote_url || '') + '/view/' + modul + '/' + cmp_url + '.json');
            request.onload = function () {
                // maskOff();
                if (request.status != 200) {
                    reject();
                    return msg('Komponenst nem sikerült betölteni', 'error');
                }
                var data;
                try {
                    data = JSON.parse(request.responseText);
                } catch (error) {
                    reject();
                    return msg('Komponenst nem sikerült betölteni', 'error');
                }

                var out = {};
                if (data.s && data.s.length != 0) out = eval("(function() { return " + data.s + "})();");
                if (data.t && data.t != 0) out.template = data.t;
                if (data.c && data.c != 0) {
                    if (/^<Grid/.test(out.template)) {
                        //Gridnek nincs slotja, ezért nem kerül bele
                        out.template = '<span v-if="$options.styleDataTitok" v-html="$options.styleDataTitok" style="display: none"></span>' + out.template;
                    } else {
                        out.template = out.template.replace(/>/, '><span v-if="$options.styleDataTitok" v-html="$options.styleDataTitok" style="display: none"></span>');
                    }
                    out.styleDataTitok = "<style>" + data.c + "</style>";
                }
                cLoadData[modul + '/' + cmp_url] = out;
                resolve(out);
            };

            request.send(null);
        });
    });
}
