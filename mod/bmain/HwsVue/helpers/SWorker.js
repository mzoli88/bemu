global.SWorker = {

    worker: null,
    wait: false,

    register: async function () {
        if (!window.SharedWorker) return;
        this.worker = new SharedWorker("workerv104.js", { name: "HWS_Worker" });
        this.worker.port.start();
        this.worker.port.onmessage = this.onMessage;
        const chanel = new BroadcastChannel("hws_tokenrefresh");
        chanel.onmessage = this.onMessage;
        this.login();
        if (getConfig().user_inanctive_timeout) {
            // window.onclick = ()=>this.onActive();
            // window.onmousedown = ()=>this.onActive();
            window.onmousemove = () => this.onActive();
            window.onkeydown = () => this.onActive();
        }
    },

    onActive: function () {
        if (this.wait == true) return;
        this.wait = true;
        this.worker.port.postMessage({ type: 'notinactive' });
        setTimeout(() => {
            this.wait = false;
        }, 10000);
    },

    onMessage: function (e) {
        switch (e.data.type) {
            case 'login':
                if (Auth.logged == true) break;
                if (!e.data.value) break;
                Auth.logged = true;
                Auth.token = e.data.value;
                sessionStorage.setItem('WebToken', e.data.value);
                break;
            case 'logout':
                if (Auth.logged == false) break;
                Auth.logged = false;
                Auth.token = null;
                sessionStorage.removeItem('WebToken');
                location.reload();
                break;
            case 'ianctivelogout':
                msg('Inaktivitás miatt a felhasználó ki lett léptetve!', 'error', () => {
                    maskOn();
                    location.reload()
                });
                if (Auth.logged == false) break;
                Auth.logged = false;
                Auth.token = null;
                sessionStorage.removeItem('WebToken');
                break;
            case 'darkmode':
                DarkMode(e.data.value);
                break;
            case 'refresh':
                if (Auth.logged == false) break;
                setNotificationCount(e.data.value.notifications, e.data.noRefreshMessage);
                break;
            case 'maintanceOn':
                var title = 'Az oldal jelenleg karbantartás alatt van';
                var message = 'Köszönjük türelmét!';
                if (isObject(e.data.value) && e.data.value.title) title = e.data.value.title;
                if (isObject(e.data.value) && e.data.value.message) message = e.data.value.message;

                msg(title, 'maintenance', null, message);
                break;
            case 'maintanceOff':
                location.reload();
                break;
            case 'QuequeResponse':
                var quequeRes = JSON.parse(e.data.value);
                Queque.run = true;
                Queque.name = quequeRes.name;
                Queque.signal = quequeRes.signal;
                Queque.can_cancel = quequeRes.can_cancel;

                if (quequeRes.ready) {


                    if (quequeRes.has_err) {
                        msg(Queque.name + ' hiba!', 'error', null, quequeRes.has_err);
                    } else {
                        tMsg.s(Queque.name + ' befejeződött');
                    }

                    if (quequeRes.content && quequeRes.content.download) {
                        Queque.download = quequeRes.content.download;
                    } else {
                        Queque.run = false;
                        Queque.name = '';
                    }
                    Queque.can_cancel = false;

                }
                break;
            case 'QuequeOff':
                Queque.download = false;
                Queque.run = false;
                Queque.name = '';
                break;
        }
    },

    login: function () {
        if (!this.worker) return;
        if (empty(Auth.token)) return;
        this.worker.port.postMessage({ type: 'login', value: Auth.token, config: getConfig().name, user_inanctive_timeout: getConfig().user_inanctive_timeout });
    },
    logout: function () {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'logout' });
    },
    getToken: function () {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'gettoken' });
    },
    darkmode: function (val) {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'darkmode', value: val });
    },
    refresh: function (val) {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'refresh', noRefreshMessage: val });
    },
    maintanceOn: function (val) {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'maintanceOn', value: val });
    },
    QuequeOn: function (val) {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'QuequeOn', value: JSON.stringify(val), ready: val.ready });
    },
    QuequeOff: function () {
        if (!this.worker) return;
        this.worker.port.postMessage({ type: 'QuequeOff' });
    },

};