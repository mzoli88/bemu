import { reactive } from "vue";

global.getStore = function (name) {

  let tmp = name.split('|');
  let st_name = tmp.shift();
  if (!MajaxManager.registered[st_name]) {
    if (st_name.includes('/')) {
      new Majax((getConfig().remote_url || '') + "/remote/" + st_name).store(st_name);
    } else if (st_name.includes('.')) {
      new Majax((getConfig().remote_url || '') + "/remote/" + st_name.replaceAll('.', '/')).store(st_name);
    } else {
      if (global.getActiveModul) {
        var active_menu = getActiveMenu()
        menu = active_menu ? getActiveModul() + '.' + active_menu : getActiveModul();
        return getStore(menu + '.' + st_name);
      } else {
        if (!st_name.includes('.')) {
          var menu = getActiveMenu();
          if (!MajaxManager.registered[menu + '.' + st_name]) {
            new Majax((getConfig().remote_url || '') + '/remote/api/' + menu + '/' + st_name).store(menu + '.' + st_name);
          }
          st_name = menu + '.' + st_name;
        }
      }
    }
  }

  for (var i in tmp) {
    let tmp2 = tmp[i].split('=');
    MajaxManager.registered[st_name].fixQuery(tmp2[0], tmp2[1]);
  }
  return MajaxManager.registered[st_name];
};

global.hasStore = function (name) {
  return MajaxManager.registered[name.split('|').shift()];
};

global.isLoading = function () {
  return global.MajaxManager.loading;
};

global.MajaxManager = reactive({
  lastTime: null, //queque delay time
  registered: {},
  queue: [],
  queueLoadedFn: [],
  loading: false,
  timeout: 480000,
  deleteAll: function () {
    this.flushLoaded();
    this.loading = false;
    this.queue = [];
  },
  isLoaded: function (fn) {
    this.queueLoadedFn.push(fn);
    if (this.queue.length == 0) this.flushLoaded();
  },
  flushLoaded: function (fn) {
    if (this.queueLoadedFn.length) {
      do {
        this.queueLoadedFn.pop().call(this);
      } while (this.queueLoadedFn.length);
    }
  }
});

global.Auth = reactive({
  logged: false,
  token: null,
});

global.getCookie = function (name) {
  var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  if (match) return decodeURIComponent(match[2]);
}

global.tokenHandler = {

  login: function (params, callback) {
    var me = this;
    getStore('auth/login').send({
      noErr: true,
      timeout: 60000,
      type: "POST",
      params: params,
      callback: function (s, data, c) {
        me.callback(s, data, c);
        if (callback) callback(s, data, c);
      }
    })
  },

  callback: function (s, data, c) {

    var me = this;

    if (s && data.token) {
      Auth.token = data.token;
      sessionStorage.setItem("WebToken", data.token);
      Auth.logged = true;
      SWorker.login();
    } else {
      me.logout(true);
    }
  },

  logout: function (noLoad) {
    if (!noLoad) {
      getStore('auth/logout').send({
        type: "DELETE",
        noErr: true,
        useToken: true
      });
    }
    Auth.token = null;
    Auth.logged = false;
    sessionStorage.removeItem("WebToken");
    SWorker.logout();
    // location.reload();
  },

};

global.Majax = class Majax {

  constructor(url) {
    this.loading = false;
    this.$url = url;
    this.$isJson = true;
    this.lastResponse = "";
    this.$useToken = false;
    if (global.defaultToken) this.$useToken = true;

    //headers
    this.$headers = {};

    //url params
    this.$fix_query = {};
    this.$search_query = {};
    this.$sort_page_query = {};

    //display data
    this.$displayData = reactive({
      total: 0,
      maxPage: 0,
      page: 1,
      perPage: 25,
    });

    //events
    this.$on_save = {};
    this.$on_View = {};
    this.$on_load = {};
    this.$on_before_load = {};
  }

  $getQParams(get, isUrl) {
    var ret = [];
    for (var i in get) {
      var d = get[i];
      if (isArray(d)) d = JSON.stringify(d);
      ret.push(
        encodeURIComponent(i) + "=" + encodeURIComponent(d)
      );
    }
    if (isUrl) {
      return ret.length ? "?" + ret.join("&") : "";
    } else {
      return ret.join("&");
    }
  }

  $doXhr(options) {

    var me = this,
      xhr = new XMLHttpRequest();

    xhr.open(options.method, options.url + me.$getQParams(options.query, true), !options.wait);

    if (global.getActiveEntity) xhr.setRequestHeader("Active-Entity", getActiveEntity());

    if (!options.wait) {
      xhr.setRequestHeader("Cache-Control", "no-cache, no-store, must-revalidate");
      xhr.timeout = MajaxManager.timeout;
      if (options.timeout) xhr.timeout = options.timeout;
      xhr.responseType = 'blob';
    }

    // if (document.cookie.indexOf('XSRF-TOKEN=') != -1) xhr.setRequestHeader('X-XSRF-TOKEN', getCookie('XSRF-TOKEN'));

    if (options.useToken && Auth.token) xhr.setRequestHeader('Authorization', 'Bearer ' + Auth.token);

    for (var i in options.headers) {
      xhr.setRequestHeader(i, options.headers[i]);
    }

    xhr.onreadystatechange = function (e) {
      if (this.readyState != 4) return;

      //túl sok kérés
      if (this.status == 429) {
        maskOn();
        tMsg.e("Túl sok kérés miatt a szerver biztonsági okokból letíltotta a műveleteket.<br>Ez egy átmeneti állapot, köszönjük türelmét!", 20);
        return setTimeout(x => {
          me.$doXhr(options);
          maskOff();
        }, 20000);
      }

      // download
      var download = false;

      if (this.getResponseHeader('Content-Disposition')) {
        var contentDispo = this.getResponseHeader('Content-Disposition');
        if (contentDispo) {
          var a = document.createElement('a');
          var url = window.URL.createObjectURL(this.response);
          a.href = url;
          a.download = decodeURI(contentDispo.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)[1].replaceAll('"', ''));
          a.dispatchEvent(new MouseEvent('click'));
          window.URL.revokeObjectURL(url);
          download = true;
        }
      }

      // async function delay() {
      // if (MajaxManager.queue.length == 0) return me.$queueRemove();
      // var interval = global.MajaxManager.lastTime - performance.now() + 100;
      // if (interval > 0) await new Promise(resolve => setTimeout(resolve, interval || 100));
      // me.$queueRemove();
      // }

      var status = this.status,
        success = false;
      if (status >= 200 && status < 300) success = true;

      if (download) {
        if (options.on) options.on(success, '{}', status);
        options.$finished = true;
        me.$queueRemove()
      } else if (this.response instanceof Blob) {
        this.response.text().then(function (response) {
          if (options.on) options.on(success, response, status);
          options.$finished = true;
          me.$queueRemove()
        });
      } else {
        if (options.on) options.on(success, this.response, status);
        options.$finished = true;
        me.$queueRemove()
      }
      return true;
    };

    xhr.send(options.body);
  }

  $queueAdd(options) {
    if (options.hide) return this.$doXhr(options);
    if (options.queueFirst) {
      MajaxManager.queue.unshift(options);
    } else {
      MajaxManager.queue.push(options);
    }
    this.$queueLoad();
  }

  $queueRemove() {
    for (var i in MajaxManager.queue) {
      if (MajaxManager.queue[i].$finished) MajaxManager.queue.splice(i, 1);
    }
    MajaxManager.loading = false;
    if (MajaxManager.queue.length > 0) {
      this.$queueLoad();
    } else {
      // maskOff();
      MajaxManager.flushLoaded();
    }
  }

  $queueLoad() {
    if (MajaxManager.loading) return;
    MajaxManager.loading = true;
    global.MajaxManager.lastTime = performance.now();
    this.$doXhr(MajaxManager.queue[0]);
  }

  $getOptions(params) {
    var options = {};
    for (var i in params) {
      if (isObject(params[i])) {

        options.params = params[i];
        continue;
      }
      if (isFunction(params[i])) {
        options.callback = params[i];
        continue;
      }
      if (isString(params[i]) && params[i] == 'noErr') {
        options.noErr = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'wait') {
        options.wait = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'hide') {
        options.hide = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'noSortPage') {
        options.noSortPage = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'noTotal') {
        options.noTotal = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'newTab') {
        options.newTab = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'bigPage') {
        options.bigPage = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'noSearch') {
        options.noSearch = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'debug') {
        options.debug = true;
        continue;
      }
      if (isString(params[i]) && params[i] == 'noCbEvents') {
        options.noCbEvents = true;
        continue;
      }
      if (isNumber(params[i]) || isString(params[i])) {
        options.id = params[i];
        continue;
      }
    }
    return options;
  }

  $setSortPageQuery(p, v) {
    if (empty(p)) {
      this.$sort_page_query = {};
    }
    if (isObject(p)) {
      this.$sort_page_query = p;
    }
    if (isString(p)) {
      if (empty(v)) {
        delete this.$sort_page_query[p];
      } else {
        this.$sort_page_query[p] = v;
      }
    }
    return this;
  }

  noJson() {
    this.$isJson = false;
    return this;
  }

  store(name) {
    if (name) {
      if (MajaxManager.registered[name]) throw 'Store exist!';
      MajaxManager.registered[name] = this;
      this.$storeName = name;
    }
    return this;
  }

  useToken() {
    this.$useToken = true;
    return this;
  }

  setHeader(key, value) {
    if (empty(value)) {
      delete this.$headers[key];
    } else {
      this.$headers[key] = value;
    }
    return this;
  }

  onLoad(fn, key) {
    if (!isFunction(fn)) throw "onLoad param not function!";
    if (!key) throw "onLoad param neads key!";
    if (!key.cmp_id) throw "key not component!";
    if (this.$on_load[key.cmp_id]) throw "store on_load key exist!";
    this.$on_load[key.cmp_id] = fn;
    return this;
  }

  onBeforeLoad(fn, key) {
    if (!isFunction(fn)) throw "onBeforeLoad param not function!";
    if (!key) throw "onBeforeLoad param neads key!";
    if (!key.cmp_id) throw "key not component!";
    if (this.$on_before_load[key.cmp_id]) throw "store on_before_load key exist!";
    this.$on_before_load[key.cmp_id] = fn;
    return this;
  }

  onView(fn, key) {
    if (!isFunction(fn)) throw "onView param not function!";
    if (!key) throw "onView param neads key!";
    if (!key.cmp_id) throw "key not component!";
    if (this.$on_View[key.cmp_id]) throw "store onView key exist!";
    this.$on_View[key.cmp_id] = fn;
    return this;
  }

  onSave(fn, key) {
    if (!isFunction(fn)) throw "onSave param not function!";
    if (!key) throw "onSave param neads key!";
    if (!key.cmp_id) throw "key not component!";
    if (this.$on_save[key.cmp_id]) throw "store on_save key exist!";
    this.$on_save[key.cmp_id] = fn;
    return this;
  }

  unmountEvents(key) {
    if (!key.cmp_id) throw "key not component!";
    if (this.$on_load[key.cmp_id]) delete this.$on_load[key.cmp_id];
    if (this.$on_View[key.cmp_id]) delete this.$on_View[key.cmp_id];
    if (this.$on_save[key.cmp_id]) delete this.$on_save[key.cmp_id];
    if (this.$on_before_load[key.cmp_id]) delete this.$on_before_load[key.cmp_id];
    return this;
  }

  fixQuery(p, v) {
    if (empty(p)) {
      this.$fix_query = {};
    }
    if (isObject(p)) {
      this.$fix_query = p;
    }
    if (isString(p)) {
      if (empty(v)) {
        delete this.$fix_query[p];
      } else {
        this.$fix_query[p] = v;
      }
    }
    return this;
  }

  search(s) {
    s = s || {};
    this.$search_query = s;
    return this;
  }

  sort(prop, asc) {
    //delete sort
    this.$setSortPageQuery('sort>');
    this.$setSortPageQuery('sort<');

    //setSort
    if (asc === true) this.$setSortPageQuery('sort>', prop);
    if (asc === false) this.$setSortPageQuery('sort<', prop);
    return this;
  }

  page(page) {
    this.$setSortPageQuery('page', page);
    this.$displayData.page = page;
    return this;
  }

  perPage(perPage) {
    this.$setSortPageQuery('per-page', perPage);
    this.$displayData.perPage = perPage;
    return this;
  }

  load() {
    return this.send(this.$getOptions(arguments));
  }

  list() {
    return this.send(this.$getOptions(arguments));
  }

  post() {
    var op = this.$getOptions(arguments);
    op.type = "POST";
    return this.send(op);
  }

  create() {
    var op = this.$getOptions(arguments);
    op.type = "POST";
    return this.send(op);
  }

  put() {
    var op = this.$getOptions(arguments);
    op.type = "PUT";
    return this.send(op);
  }

  update() {
    var op = this.$getOptions(arguments);
    op.type = "PUT";
    return this.send(op);
  }

  delete() {
    var op = this.$getOptions(arguments);
    op.type = "DELETE";
    return this.send(op);
  }

  export() {
    var op = this.$getOptions(arguments);
    op.extraUrl = "/export";
    return this.send(op);
  }

  download() {
    var op = this.$getOptions(arguments);
    op.extraUrl = "/download";
    return this.send(op);
  }

  import() {
    var op = this.$getOptions(arguments);
    op.extraUrl = "/import";
    op.download = true;
    return this.send(op);
  }

  send(op) {
    op = op || {};
    op.type = op.type || "GET";
    op.params = op.params || {};

    var me = this, headers, query, callbacks;
    me.loading = true;

    if (op.type == "GET") {
      headers = { ...me.$headers };
      query = { ...me.$fix_query, ...op.params };
      if (!op.noSortPage) query = { ...me.$sort_page_query, ...query };
      if (!op.noSearch) query = { ...me.$search_query, ...query };

      if (op.bigPage) query['per-page'] = 600;
      if (op.debug) query['debug'] = 1;

      delete op.params;

      if (me.$isJson) {
        headers['Content-Type'] = 'application/json; charset=utf-8';
        headers['Accept'] = 'application/json';
      }

      if (op.id) {
        callbacks = me.$on_View;
      } else {
        callbacks = me.$on_load;
        for (var i in me.$on_before_load) {
          me.$on_before_load[i].call(me);
        }
      }

    } else {
      headers = { ...me.$headers };
      query = { ...me.$fix_query };
      if (me.$isJson) {
        headers['Content-Type'] = 'application/json';
        if (!isString(op.params)) op.params = JSON.stringify(op.params);
      } else {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
        if (!isString(op.params)) op.params = me.$getQParams(op.params);
      }
      callbacks = me.$on_save;
    }
    if (op.newTab) {
      me.loading = false;

      var form = document.createElement("form");
      form.style.display = "none";
      form.target = "_blank";

      if (me.$useToken || op.useToken) {
        var input = document.createElement("input");
        input.type = "text";
        input.value = 'Bearer ' + Auth.token;
        input.name = 'oauthToken';
        form.appendChild(input);
      }

      form.method = "POST";
      form.action = me.$url + (op.id ? "/" + op.id : "") + (op.extraUrl ? op.extraUrl : '') + me.$getQParams(query, true);
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
      return;
    }
    me.$queueAdd({
      method: op.type,
      url: me.$url + (op.id ? "/" + op.id : "") + (op.extraUrl ? op.extraUrl : ''),
      headers: headers,
      query: query,
      body: op.params,
      queueFirst: op.queueFirst,
      useToken: me.$useToken || op.useToken || false,
      timeout: op.timeout || false,
      wait: op.wait || false,
      hide: op.hide || false,
      on: function (success, response, status) {

        try {
          me.$lastResponse = me.$isJson ? JSON.parse(response) : response;
        } catch (error) {
          success = false;
          me.$lastResponse = response;
        }

        if (status != 200) {
          success = false;
        }

        //karbantartás mód
        if (status == 0 || status == 503) {
          // dd(11111, status);
          maskOff();
          var title = 'Az oldal jelenleg karbantartás alatt van (státusz: ' + (status || '0') + ')';
          var message = 'Köszönjük türelmét!';
          if (isObject(me.$lastResponse) && me.$lastResponse.title) title = me.$lastResponse.title;
          if (isObject(me.$lastResponse) && me.$lastResponse.message) message = me.$lastResponse.message;
          if (SWorker) SWorker.maintanceOn({
            title: title,
            message: message
          }); else {
            msg(title, 'maintenance', null, message);
          }

          return;
        } else {
          msg('', 'off');
        }

        if (success == false && !op.noErr) {

          switch (status) {
            case 422:
              //validálás
              break;
            case 401:
              // msg('Nincs belépve!', 'error');
              if (isFunction(global.On_401)) global.On_401();
              if (global.defaultToken) tokenHandler.logout();
              if (tMsg) tMsg.e('Nincs belépve!');
              break;
            case 403:
              msg('Nincs jogosultság!', 'error');
              break;
            default:
              var title = 'Hiba történt!';
              var message = '';
              if (isString(me.$lastResponse)) {
                if (/^\{.*\}$/.test(me.$lastResponse)) me.$lastResponse = JSON.parse(me.$lastResponse);
              }

              if (isObject(me.$lastResponse) && me.$lastResponse.title) title = me.$lastResponse.title;
              if (isObject(me.$lastResponse)) message = me.$lastResponse.message;

              msg(title, 'error', null, message);
              //engedjük tovább, hogy egyéb komponensek is tudjanak hibát kezelni
              break;
          }
        }


        if (isObject(me.$lastResponse) && !empty(me.$lastResponse.total)) {
          if (!op.noTotal) {
            me.$displayData.total = me.$lastResponse.total || 0;
            if (me.$sort_page_query['per-page'] && me.$displayData.total != 0) {
              me.$displayData.maxPage = Math.ceil(me.$displayData.total / me.$sort_page_query['per-page']) || 1;
            } else {
              me.$displayData.maxPage = 1;
            }
          }
        } else {
          if (op.type == "GET") {
            if (me.$lastResponse && isArray(me.$lastResponse.data) && !op.noTotal) {
              me.$displayData.total = me.$lastResponse.data.length;
            }
          }
        }
        if (!op.noCbEvents) {
          for (var i in callbacks) {
            callbacks[i].call(me, success, me.$lastResponse, status);
          }
        }

        if (op.callback) op.callback.call(me, success, me.$lastResponse, status);
        me.loading = false;
      }
    });
    return me;
  }

}
