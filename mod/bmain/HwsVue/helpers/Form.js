import { toRaw } from "vue";

global.fieldMixin = {
  emits: ['change'],
  props: {
    required: Boolean,
    disabled: Boolean,
  },
  data: function () {
    return {
      value: null
    };
  },
  watch: {
    value: {
      deep: true,
      handler: function (val) {
        var record = this.getRecord ? this.getRecord() : null;
        this.$emit('change', toRaw(val), this, record);
      },
    },
  },
};
export default {
  install: function (Vue) {
    Vue.config.globalProperties.formGet = this.formGet;
    Vue.config.globalProperties.formSet = this.formSet;
    Vue.config.globalProperties.formReset = this.formReset;
    Vue.config.globalProperties.formValidate = this.formValidate;
    Vue.config.globalProperties.findFields = this.findFields;
    Vue.config.globalProperties.getFields = this.findFields;
    Vue.config.globalProperties.formSetErrors = this.formSetErrors;
  },
  findFields: function (extrafind, noDisplayAndDisabled) {
    var findOptions = ["Field", { $hasno: { noField: true } }],
      out;
    if (extrafind) findOptions.push(extrafind);
    if (noDisplayAndDisabled) {
      findOptions.push({ $hasno: { type: 'display' } });
      findOptions.push({ $hasno: { isDisabled: true } });
      findOptions.push({ $hasno: { noEdit: true } });
      findOptions.push({ $hasno: { hidden: true } });
      findOptions.push({ $hasno: { hiddenBecauseRowData: true } });
    }

    out = this.getComps(findOptions);

    out.get = function (noEmpty) {
      var out = {};
      for (let i = 0; i < this.length; i++) {
        if (empty(this[i].name)) continue;
        var val = this[i].getValue();
        if (!noEmpty || (noEmpty && !empty(val) && val !== false)) {
          out[this[i].name] = val;
        }
      }
      return out;
    };
    out.set = function (data) {
      for (let i = 0; i < this.length; i++) {
        if (isFunction(this[i].ifRowData)) {
          // itt ellenőrizzük, hogy rowData alapján megjelenhet-e egy mező
          this[i].hiddenBecauseRowData = !this[i].ifRowData.call(this[i], data);
        }
        if (data.hasOwnProperty(this[i].name)) {
          this[i].setValue(data[this[i].name]);
        }
      }
      return this;
    };
    out.reset = function () {
      for (let i = 0; i < this.length; i++) {
        this[i].reset();
      }
      return this;
    };
    out.validate = function () {
      var out = true;
      for (let i = 0; i < this.length; i++) {
        if (empty(this[i].name)) continue;
        if (!this[i].validate()) out = false;
      }
      return out;
    };
    out.setValue = function (val) {
      var out = true;
      for (let i = 0; i < this.length; i++) {
        this[i].setValue(val);
      }
      return out;
    };
    out.run = function (fn_name, input) {
      var out = true;
      for (let i = 0; i < this.length; i++) {
        this[i][fn_name](input);
      }
      return out;
    };
    out.hide = function () {
      for (let i = 0; i < this.length; i++) {
        this[i].hide();
      }
      return this;
    };
    out.show = function () {
      for (let i = 0; i < this.length; i++) {
        this[i].show();
      }
      return this;
    };
    out.each = function (fn_name) {
      for (let i = 0; i < this.length; i++) {
        fn_name(this[i], i);
      }
    };

    return out;
  },
  formGet: function (noEmpty, find) {
    return this.findFields(find, true).get(noEmpty);
  },

  formSet: function (data, find) {
    return this.findFields(find).set(data);
  },

  formReset: function (find) {
    return this.findFields(find).reset();
  },

  formValidate: function (find) {
    return this.findFields(find, true).validate();
  },

  formSetErrors: function (Obj) {
    var fields = this.findFields();
    for (let i = 0; i < fields.length; i++) {
      fields[i].errors = [];
      if (Obj.hasOwnProperty(fields[i].name)) {
        var err = Obj[fields[i].name];
        if (isArray(err)) {
          for (var j in err) {
            fields[i].errors.push(err[j]);
          }
        } else {
          fields[i].errors.push(err);
        }
      }
    }
  }
};
