var hashtimeout,
  compHashChange = false,
  isHahsChange = false,
  hahsChangeOld = false,
  hash_started = false,
  page_loaded = false,
  stop_change_event = 0;

setTimeout(() => page_loaded = true, 30 * 1000); // biztonságból levédjük, hogy legyen hash kezelés, feltételezzük, hogy az oldal fél perc után biztosab betöltődött

global.setHash = function setHash(data) {
  if (isArray(data)) {
    data = data.join("/");
  }
  location.hash = data;
};

function hashChangeOff() {
  // dd('hashChangeOff');
  // hahsChangeOld = false;
  isHahsChange = false;
}

export default {
  install: function (Vue) {
    Vue.config.globalProperties.getCpHash = this.getCpHash;
    Vue.config.globalProperties.setHash = this.compSetHash;
    Vue.config.globalProperties.$getHash = this.get;
    Vue.config.globalProperties.$setHash = this.set;
    Vue.config.globalProperties.$onHashChange = this.on;
    Vue.config.globalProperties.$getParentHashCount = this.getParentHashCount;
    Vue.config.globalProperties.$doCmpHash = this.doCmpHash;
    Vue.config.globalProperties.$startHash = this.startHash;
    Vue.config.globalProperties.$lastHash = null;
  },

  startHash: function () {
    //levédjük, hogy véletlenül se legyen több hashchange event event
    if (hash_started) return;
    hash_started = true;
    this.$onHashChange((hash, oldHash) => {
      // dd('onHashChange');
      //hash change-nak csak akkor szabad lennie, ha egy külső behatás segítségével változik meg a hash
      isHahsChange = true;
      hahsChangeOld = oldHash;
      var cmp = this;
      global.MajaxManager.deleteAll();
      for (var i in hash) {
        cmp = cmp.down({
          $hash: Function,
        });
        if (!cmp) {
          this.$nextTick(hashChangeOff);
          return;
        }
        if (hash[i] != oldHash[i]) {
          cmp.$hash(hash[i], hahsChangeOld);

          var fncmp = () => {
            this.$nextTick(x => {
              i++;
              cmp = cmp.down({
                $hash: Function,
              });
              if (cmp) {
                cmp.$hash(hash[i], hahsChangeOld);
                fncmp();
              } else {
                this.$nextTick(hashChangeOff);
              }
            });
          }
          fncmp();
          return;
        }
      }
      this.$nextTick(hashChangeOff);
    });
  },

  getCpHash: function () {
    // dd (compHashChange , isHahsChange);
    if (compHashChange && !isHahsChange) return this.$lastHash;
    var hash = this.$getHash(),
      parentsHashcount = this.$getParentHashCount();
    return hash[parentsHashcount];
  },

  doCmpHash: function () {
    // dd ('doCmpHash');
    if (this.$hash) {
      this.$nextTick(x => {
        let hashParam = this.getCpHash();
        if (!hashParam) return;
        this.$hash(hashParam, hahsChangeOld);
        this.$lastHash = hashParam;
      });
    }
  },

  //Feladata, hogy a kompones változás alapján előállítson egy hash-t.
  compSetHash: function (data) {
    this.$lastHash = data;
    if (isHahsChange) return;

    if (page_loaded == false && this.getCpHash()) {
      if ((this.$getParentHashCount() + 1) != this.$getHash().length) return;
    }
    page_loaded = true;

    compHashChange = true;
    clearTimeout(hashtimeout);

    // if (hahsChangeOld) {
    //   this.$nextTick(x =>
    //     hahsChangeOld = false
    //   );
    // }

    MajaxManager.isLoaded(x => {
      //ne egymás után következzenek a hash változások
      hashtimeout = setTimeout(x => {

        var cmp = this.$root;
        var newhash = [];
        while (cmp) {
          cmp = cmp.down({
            $hash: Function,
          });
          if (cmp) newhash.push(cmp.$lastHash)
        }

        if (newhash.join('/') != this.$getHash().join('/')) {
          stop_change_event++;
          // dd('stop_change_event add', stop_change_event, newhash, this.$getHash());
          this.$setHash(newhash);
        }
        compHashChange = false;
      }, 400);
    });
  },

  get: function () {
    var hash = decodeURI(location.hash.substr(1));
    if (!hash) return [];
    return hash.split("/");
  },

  set: function (data) {
    if (isArray(data)) {
      data = data.join("/");
    }
    setHash(data);
  },

  on: function (fn, fire, scope) {
    var me = this;

    function hevent(e) {

      if (stop_change_event) {
        //ne fusson az event, ha compsetVan folyamatban
        stop_change_event--;
        if(stop_change_event == 0)hahsChangeOld = false;
        return;
      }

      scope = scope || this;

      var newhash = e.newURL.split('#').pop().split('/');
      var oldhash = e.oldURL.split('#').pop().split('/');

      var ret = fn.call(scope, newhash, oldhash);
      if (ret) me.$setHash(ret);
    }
    addEventListener("hashchange", hevent);
    if (fire) hevent();
  },


  getParentHashCount: function (count) {
    count = count || 0;
    const comp = this.up({
      $hash: Function,
    });
    if (comp) {
      count++;
      count = comp.$getParentHashCount(count);
    }
    return count;
  },

};
