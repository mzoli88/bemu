
import { reactive } from "vue";

global.getConfig = function () {
    if (global.config) return global.config;
    var request = new XMLHttpRequest();
    request.open('GET', 'config.php', false);
    request.send(null);
    global.config = reactive(JSON.parse(request.responseText));
    return global.config;
};