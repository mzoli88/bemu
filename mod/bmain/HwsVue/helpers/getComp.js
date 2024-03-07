function checkOne(val, find) {

  switch (find) {
    case String:
      if (!isString(val)) {
        return false;
      }
      break;
    case Array:
      if (!isArray(val)) {
        return false;
      }
      break;
    case Object:
      if (!isObject(val)) {
        return false;
      }
      break;
    case Function:
      if (!isFunction(val)) {
        return false;
      }
      break;
    case Number:
      if (!isNumeric(val)) {
        return false;
      }
      break;
    default:
      if (isFunction(find)) {
        return find(val);
      }
      if (isRegexp(find)) {
        if (val) return find.test(val);
        return false;
      }
      if (val !== find) {
        return false;
      }
      break;
  }
  return true;
}

function check(find, cmp) {
  if (isString(find)) {
    return cmp.$options.name == find;
  }
  if (isArray(find)) {
    var tmp = true;
    for (var e in find) {
      if (!check(find[e], cmp)) {
        tmp = false;
        break;
      }
    }
    return tmp;
  }

  if (isObject(find)) {
    for (var i in find) {
      if (i == "$hasno") {
        if (check(find[i], cmp)) return false;
        break;
      }

      var val = cmp[i];

      if (val === undefined && cmp.$attrs) {
        val = cmp.$attrs[i];
      }
      if (isArray(find[i])) {
        var tmp = false;
        for (var j in find[i]) {
          if (checkOne(val, find[i][j])) {
            tmp = true;
          }
        }
        if (!tmp) return false;
      } else {
        if (!checkOne(val, find[i])) return false;
      }
    }
    return true;
  }
}

var comp_id_count = 0;

export default {
  childrenmixin: {
    emits: ["onMounted"],

    beforeCreate: function () {
      comp_id_count++;
      this.cmp_id = comp_id_count;
    },
    mounted: function () {
      this.$emit('onMounted', this);
      this.$doCmpHash();
      this.isMounted = true;
      this.isActivated = true;
      // dd ('mounted',this.isMounted);
      if (this.$parent) {
        const index = this.$parent.children.indexOf(this);
        if (index == -1) {
          // dd ('notFound');
          // this.$parent.children.splice(index, 1);
          this.$parent.children.push(this);
        }
      }
    },

    activated: function () {
      this.isActivated = true;
      // dd ('activated');
      if (this.$parent) {
        const index = this.$parent.children.indexOf(this);
        if (index == -1) {
          // dd ('notFound');
          // this.$parent.children.splice(index, 1);
          this.$parent.children.push(this);
        }
      }

    },
    data: function () {
      if (this.$parent) {
        this.$parent.children.push(this);
      }
      this.children = [];
      return {
        isMounted: false,
        isActivated: false,
      };
    },
    computed: {
      _self: function () {
        return this;
      }
    },
    deactivated: function () {
      this.isActivated = false;
      // dd ('deactivated');
      if (this.$parent) {
        const index = this.$parent.children.indexOf(this);
        if (index > -1) {
          this.$parent.children.splice(index, 1);
        }
      }
    },
    unmounted: function () {
      this.isMounted = false;
      if (this.store) {
        //fontos, hogy fixquary ne legyen pl combo esetében
        getStore(this.store.split("|").shift()).unmountEvents(this);
      }
      // dd ('unmounted',this.isMounted);
      if (this.$parent) {
        const index = this.$parent.children.indexOf(this);
        if (index > -1) {
          this.$parent.children.splice(index, 1);
        }
      }
    }
  },

  install: function (App) {
    App.config.globalProperties.up = this.up;
    App.config.globalProperties.down = this.down;
    App.config.globalProperties.getComps = this.getComps;
  },

  down: function (find, notRecursive) {
    if (empty(this.children)) return;
    for (let i = 0; i < this.children.length; i++) {
      let c = this.children[i];
      if (!find) return c;

      if (!c._inactive && !c.hidden) {
        if (check(find, c)) {
          return c;
        } else {
          if (!notRecursive) {
            let ct = c.down(find);
            if (ct) return ct;
          }
        }
      }
    }
    return;
  },
  getComps: function (find, out) {
    out = out || [];

    if (empty(this.children)) return out;

    for (let i = 0; i < this.children.length; i++) {
      let c = this.children[i];
      if (!c._inactive) {
        if (check(find, c)) {
          out.push(c);
        }
        c.getComps(find, out);
      }
    }
    return out;
  },
  up: function (find) {
    let c = this.$parent;
    if (!find) return c;
    if (c) {
      if (check(find, c)) {
        return c;
      } else {
        return c.up(find);
      }
    }
    return;
  },
};
