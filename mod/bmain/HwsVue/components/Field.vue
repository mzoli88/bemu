<template>
  <div class="Field Ftext" v-if="!hidden && !hiddenBecauseRowData" :class="[
    errors.length ? 'hasError' : null,
    validators.required ? 'required' : null,
    name,
    'type_' + input,
  ]">
    <div class="fieldLabel noselect vflex" v-if="!noLabel">
      <span :class="{ FieldList: list }" v-if="label">{{ label }}:</span>
      <span class="WarningButton icon pointer" v-if="warning" :title="warning">
        &#xf06a;
      </span>
      <span class="InfoButton icon pointer" v-if="info" :title="info">
        &#xf05a;
      </span>
    </div>
    <div class="FieldInner hflex" @keypress.enter="$emit('onEnter', $event, _self)" :class="{ disabled: isDisabled }">
      <component :is="input" :name="name" :disabled="isDisabled" :noEdit="noEdit" :required="validators.required"
        v-bind="{ ...fattrs, ...$attrs }" v-if="input" :boxes="paramboxes" @change="onChange" @blur="onBlur"
        @recordChange="onRecordChange" ref="input">
        <slot />
        <template v-for="(_, name) in $slots" v-slot:[name]="slotData">
          <slot :name="name" v-bind="slotData" />
        </template>
      </component>
      <slot v-else />
      <div class="Err fieldbody" :class="{ floatErr: floatErr }" v-if="errors.length">
        <ul>
          <li v-for="(txt, i) in errors" :key="'error_' + i">{{ txt }}</li>
        </ul>
      </div>
    </div>
    <slot name="fieldEnd" />
  </div>
</template>

<script>
import { toRaw } from "vue";

import Text from "./inputs/Text.vue";
import Number from "./inputs/Number.vue";
import Date from "./inputs/Date.vue";
import Checkbox from "./inputs/Checkbox.vue";
import File from "./inputs/File.vue";
import Searchdate from "./inputs/Searchdate.vue";
import Checkboxgroup from "./inputs/Checkboxgroup.vue";
import Radiogroup from "./inputs/Radiogroup.vue";
import Combo from "./inputs/Combo.vue";
import Display from "./inputs/Display.vue";
import Searchnumber from "./inputs/Searchnumber.vue";
import Textarea from "./inputs/Textarea.vue";
import Multicombo from "./inputs/MultiCombo.vue";
import Multifile from "./inputs/Multifile.vue";
import Dateinterval from "./inputs/Dateinterval.vue";
import Numberinterval from "./inputs/Numberinterval.vue";
import Color from "./inputs/Color.vue";
import Password from "./inputs/Password.vue";
import Entity from "./inputs/EntityCombo.vue";
import Html from "./inputs/Html.vue";
import Iconpicker from "./inputs/Iconpicker.vue";

// Figyelem!
// nem töltődik be időben a value a mezőbe ezért rendesen be kell tölteni a mezőket
// import { defineAsyncComponent } from 'vue'
// const Html = defineAsyncComponent(() => import('./inputs/Html.vue'));
// const Iconpicker = defineAsyncComponent(() => import('./inputs/Iconpicker.vue'));

export default {
  name: "Field",
  components: {
    Text,
    Number,
    Date,
    Checkbox,
    File,
    Searchdate,
    Checkboxgroup,
    Radiogroup,
    Combo,
    Display,
    Searchnumber,
    Textarea,
    Multicombo,
    Multifile,
    Dateinterval,
    Numberinterval,
    Color,
    Password,
    Entity,
    Html,
    Iconpicker,
  },

  emits: ["change", "onEnter", "recordChange", "blur"],

  props: {
    label: String,
    validation: String,
    type: {
      default: "string",
      type: String,
    },
    disabled: Boolean,
    required: Boolean,
    noLabel: Boolean,
    noEdit: Boolean,
    name: String,
    startValue: {
      default: function (props) {
        if (props.type == "rstatus") return "I";
        return null;
      },
    },
    render: Function,
    change: Function,
    warning: String,
    info: String,
    vReg: String,
    vRegText: String,
    if: [String, Array], // if="parent_id=1|search_id=2,3"   VAGY COMBO esetén rekord property-re if="parent_id.masik_mezo=1" (ahol combbo name=parent_id ott a rekordban van-e masik_mezo=1 )  
    requiredIf: String,
    evalIf: String,
    hasRowData: String,
    clearAfterChange: Boolean,
    fillOnChange: String,
    fixq: String,
    nofindFields: Boolean,
    fastFilter: Array,
    floatErr: Boolean,
    list: Boolean,
    ifRowData: Function, // Form helperek-ben van lekezelve (rowdata feltöltés esetén)
  },
  data: function () {
    var input = "Text",
      validators = {},
      fattrs = {},
      boxes = this.boxes;

    const capitalize = (s) => {
      if (typeof s !== "string") return "";
      return s.charAt(0).toUpperCase() + s.slice(1);
    };

    if (this.nofindFields) this.noField = true;

    if (this.type) {
      input = capitalize(this.type);
      switch (this.type) {
        case "slot":
          input = null;
          break;
        case "string":
          input = "Text";
          break;
        case "integer":
          input = "Number";
          break;
        case "searchdate":
        case "searchnumber":
        case "entity":
          this.noField = true;
          this.noValidation = true;
          break;
        case "datetime":
          input = "Date";
          validators.datetime = true;
          break;
        case "chgroup":
          input = "Checkboxgroup";
          break;
        case "cstatus":
          input = "Checkboxgroup";
          boxes = [
            { name: "Aktív", value: "I" },
            { name: "Inaktív", value: "N" },
          ];
          break;
        case "cigennem":
          input = "Checkboxgroup";
          boxes = [
            { name: "Igen", value: "I" },
            { name: "Nem", value: "N" },
          ];
          break;
        case "rigennem":
          input = "Radiogroup";
          boxes = [
            { name: "Igen", value: "I" },
            { name: "Nem", value: "N" },
          ];
          fattrs.igennem = true;
          break;
        case "rstatus":
          input = "Radiogroup";
          boxes = [
            { name: "Aktív", value: "I" },
            { name: "Inaktív", value: "N" },
          ];
          break;
        case "longtext":
          input = "Textarea";
          break;
        case "dateinterval":
        case "numberinterval":
          validators.interval = true;
          break;
      }
    }

    if (this.noEdit) {
      switch (input) {
        case "Date":
        case "Textarea":
        case "Html":
        case "Number":
        case "Text":
          input = "Display";
          break;
      }
    }
    // dd(input, this.type,this.noEdit);

    if (!empty(this.validation)) {
      var tmp = this.validation.split("|");
      for (var i in tmp) {
        if (tmp[i] == "date") continue;
        validators[tmp[i]] = true;
      }
    }
    if (this.required) validators.required = true;
    return {
      isDisabled: this.disabled,
      input: input,
      validators: validators,
      paramboxes: boxes || this.$attrs.boxes,
      errorChange: true,
      hidden: false,
      hiddenBecauseRowData: false,
      errors: [],
      fattrs: fattrs,
    };
  },
  mounted: function () {
    if (this.startValue) this.setValue(this.startValue);
    this.checkrequiredIf();
    this.checkChildren();
    this.checkChildren2();
    this.checkChildren3();
    this.doEvalIf();
    this.doHasRowData();
  },
  created: function () {
    this.onActivate();
    if (this.type == "entity" && !this.$root.entity) this.hide();
  },
  watch: {
    disabled: function (val) {
      this.isDisabled = val;
      if (val) this.errors = [];
    },
    startValue: function (val) {
      this.setValue(val);
    },
    required: function (val) {
      this.validators.required = val;
      this.errors = [];
    },
    hidden: function (val) {
      if (val == false && !empty(this.OldValue)) {
        this.$nextTick(() => this.setValue(this.OldValue));
      }
    },
  },
  methods: {
    getValue: function () {
      if (!this.$refs.input) return null;
      return toRaw(this.$refs.input.value);
    },
    disable: function () {
      this.isDisabled = true;
    },
    enable: function () {
      this.isDisabled = false;
    },
    onActivate: function () {
      var me = this,
        form = this.up("Form");
      if (form && form.rowData) {
        me.$nextTick(() => {
          // van olyan eset, hogy a startValue értéket hamarabb kapja meg mint a rowData-t
          if ((empty(me.getValue()) || (me.startValue == me.getValue() && me.startValue != form.rowData[me.name])) && !empty(form.rowData[me.name]) ) {
            me.$nextTick(() => {
              // dd(form.rowData[me.name], me.getValue(), me.hidden);
              me.setValue(form.rowData[me.name]);
            });
          }
        });
      }
    },
    reRender: function () {
      if (this.render) {
        var record,
          val,
          f = this.up("Form");
        if (f) {
          record = f.rowData || {};
        }
        if (!record) return;
        val = record[this.name];
        if (record) val = this.render(val, this.name, record);
        if (val === false) {
          this.hidden = true;
        } else {
          this.hidden = false;
        }

        this.$refs.input.value = val;
      }
    },
    setValue: function (val) {
      if (this.render) {
        var record,
          f = this.up("Form");
        if (f) {
          record = f.rowData || {};
        }
        val = this.render(val, this.name, record);
      }

      if (this.$refs.input) {
        //mielőtt felül írjuk az értéket hagyjuk,  hogy a komponensek maguk konvertálják át a valuet
        if (this.$refs.input.$convertValue) {
          val = this.$refs.input.$convertValue(val);
          if (empty(val)) val = null;
        }

        //ha undefined akkor olyan mező van benne, aminek nincs value értéke (pl dátum kereső)
        if (this.$refs.input.value !== undefined) {
          this.$refs.input.value = val;
        }
      }
    },
    hide: function () {
      this.hidden = true;
    },
    show: function () {
      this.hidden = false;
    },
    reset: function () {
      this.setValue(this.startValue || null);
      this.$nextTick(function () {
        this.errors = [];
      });
    },
    validate: function () {
      this.errors = [];

      if (this.isDisabled) return;
      if (this.hidden) return;
      if (this.noValidation) return;
      var val = this.getValue();

      if (!empty(val) && isString(val) && this.vReg) {
        if (!new RegExp(this.vReg).test(val)) this.errors.push(this.vRegText);
      }
      // dd(this.validators);
      for (var key in this.validators) {
        if (this.validators[key] === false) continue;
        let tmp = key.split(":"),
          isText = this.input != "Number";
        key = tmp.shift();
        tmp = tmp.join(":");
        if (Validators[key]) {
          let out = Validators[key].call(this, val, tmp, isText);
          if (out) this.errors.push(out);
        }
      }

      return this.errors.length == 0;
    },
    doHasRowData: function () {
      if (!this.hasRowData) return;
      var parent = this.up("Form");
      var rowData = parent.rowData;
      if (empty(rowData)) rowData = parent.data;
      var tmp = this.hasRowData.split(/\|/);
      tmp.forEach((x) => {
        let tmp2 = x.split(/\=/);
        let prop = tmp2[0];
        let values = tmp2[1].split(/\,/);
        let rowDataProp = rowData[prop];
        if (isObject(rowDataProp) || isArray(rowDataProp)) {
          rowDataProp = JSON.parse(JSON.stringify(rowDataProp));
          const found = rowDataProp.some(r => values.includes(r))
          if (!found) return this.hide();
        } else {
          if (rowDataProp && !values.includes("" + rowDataProp)) return this.hide();
        }

      });
    },
    doEvalIf: function () {
      if (!this.evalIf) return;
      if (eval(this.evalIf)) {
        this.show();
      } else {
        this.hide();
      }
    },
    checkPropertyIf: function (record) {

      var form = this.up("Form");
      if (!form) return;

      //ha van olyan field amiben. al választjuk el a property-t az IF-ben
      var fields = form.getComps({
        propertyIf: new RegExp("^" + this.name + "\\..*="),
        hide: Function,
        show: Function,
      });
      // dd (fields);

      fields.forEach((field) => {
        if (!record) return field.hide();

        var explode = field.if.split("=");
        var property = explode.shift().replace(/^.*\./, "");
        var value = explode.shift();
        // dd (value,property);
        // dd(record[property]);
        if (empty(record[property])) return;
        if (isString(record[property])) {
          if (record[property] == value) {
            field.show();
          } else {
            field.hide();
          }
        }
        if (isArray(record[property])) {
          if (JSON.parse(JSON.stringify(record[property])).includes(value)) {
            field.show();
          } else {
            field.hide();
          }
        }
      });
    },
    checkChildren3: function () {
      if (!this.fillOnChange) return;
      //fillOnChange ellenőrzés
      var form = this.up("Form");
      if (!form) return;

      var tmp = this.fillOnChange.split(/[\=\|]/);
      var field = form.down({
        name: tmp[0],
      });
      if (!field) return;

      var record = this.$refs.input.getRecord();
      if (!record || !record[tmp[1]]) return;
      if (empty(field.getValue())) {
        field.setValue(record[tmp[1]]);
      } else {
        //figyelmeztetés
        msg(
          '"' +
          field.label +
          '" - mező tartalma nem üres. Biztosan szeretné bővíteni?',
          "i/n",
          (s) => {
            if (s) field.setValue(field.getValue() + "\n\n" + record[tmp[1]]);
          }
        );
      }
    },
    checkChildren2: function () {
      //fixq ellenőrzés
      var form = this.up("Form");
      if (!form) return;
      var val = this.getValue(),
        fields = form.getComps({
          fixq: new RegExp(this.name),
          hide: Function,
          show: Function,
        });
      if (fields.length == 0) return;
      fields.forEach((r) => {
        if (!r.hideCount2) r.hideCount2 = {};
        if (empty(val)) {
          r.hideCount2[this.name] = false;
        } else {
          r.hideCount2[this.name] = true;
          getStore(r.store || r.$attrs.store).fixQuery(this.name, val);
        }
        if (
          Object.keys(r.hideCount2).filter((key) => r.hideCount2[key] == false)
            .length
        ) {
          r.hide();
        } else {
          r.show();
        }
      });
    },
    checkChildren: function () {
      var form = this.up("Form");
      if (!form) return;
      var val = this.getValue(),
        fields = form.getComps({
          if: new RegExp(this.name + "="),
          hide: Function,
          show: Function,
        });

      if (isNumber(val)) val += "";
      if (empty(val)) val = "";
      if (isArray(val)) {
        val = val.map((r) => r + "");
      } else {
        val = [val];
      }
      if (fields.length == 0) return;

      fields.forEach((r) => {
        var tmpif = r.if;
        if (!isArray(tmpif)) tmpif = [tmpif];
        if (!r.hideCount) r.hideCount = [];

        for (let index = 0; index < tmpif.length; index++) {
          var rif = tmpif[index].split("|")
            .filter((rif) => rif.includes(this.name))
            .shift();
          if (empty(rif)) continue;
          if (!r.hideCount[index]) r.hideCount[index] = {};
          if (rif
            .replace(this.name + "=", "")
            .split(",")
            .some((r) => val.includes(r))
          ) {
            r.hideCount[index][this.name] = true;
          } else {
            r.hideCount[index][this.name] = false;
          }
        }
      });

      fields.forEach((r) => {

        var is_ok = false;
        for (let index = 0; index < r.hideCount.length; index++) {
          const e = r.hideCount[index];
          if (Object.keys(e).filter((key) => e[key] == false).length == 0){
            is_ok = true;
            break;
          }
        }

        if (is_ok) {
          r.show();
        } else {
          r.hide();
        }
      });
    },
    checkrequiredIf: function () {
      // requiredIf
      var form = this.up("Form");
      if (!form) return;
      var val = this.getValue(),
        fields = form.getComps({
          requiredIf: new RegExp(this.name + "="),
          hide: Function,
          show: Function,
        });
      if (isNumber(val)) val += "";
      if (empty(val)) val = "";
      if (isArray(val)) {
        val = val.map((r) => r + "");
      } else {
        val = [val];
      }
      if (fields.length == 0) return;
      fields.forEach((r) => {
        let rif = r.requiredIf
          .split("|")
          .filter((rif) => rif.includes(this.name))
          .shift();
        if (!r.requiredIfhideCount) r.requiredIfhideCount = {};
        if (
          rif
            .replace(this.name + "=", "")
            .split(",")
            .some((r) => val.includes(r))
        ) {
          r.requiredIfhideCount[this.name] = true;
        } else {
          r.requiredIfhideCount[this.name] = false;
        }
        if (
          Object.keys(r.requiredIfhideCount).filter(
            (key) => r.requiredIfhideCount[key] == false
          ).length
        ) {
          r.validators.required = false;
        } else {
          r.validators.required = true;
        }
      });
    },
    onChange: function (val, input, record) {
      // dd(val);
      this.OldValue = val;
      this.$emit("change", val, this, record);
      if (this.change) this.change(val, this, record);
      this.validate();
      this.checkrequiredIf();
      this.checkChildren();
      this.checkChildren2();
      this.checkChildren3();
      this.checkPropertyIf(record);
      if (this.clearAfterChange) this.setValue();
    },
    onRecordChange: function (record) {
      this.$emit("recordChange", record, this);
    },
    onBlur: function (e) {
      this.$emit("blur", e);
    },
  },
};
</script>

<style>
textarea:focus,
input:focus {
  outline: none;
}

.Field .fieldbody {
  position: relative;
}

.WarningButton {
  color: #cc3300;
}

.InfoButton {
  color: #18cc00;
}

.floatErr {
  position: absolute !important;
  background-color: white !important;
}

.fieldBorder,
.dropdown,
.fieldButtonFront,
.fieldButton {
  border: 1px solid #d5d7db;
  background-color: #fff;
  border-radius: 4px;
  padding: 5px;
  outline: none;
}

.fieldbody {
  font-size: 15px;
  color: #333;
  margin: 2px;
}

.fieldButtonFront,
.fieldButton {
  position: relative;
  color: #646874;
  font-size: 14px;
  padding: 0;
  width: 35px;
}

.fieldButton {
  border-radius: 0;
  border-left: none;
}

.fieldButton:last-child {
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}

.fieldButtonFront {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  border-right: none;
}

.MultiFileBody .fieldBorder,
.multicomboselectedrow .fieldBorder,
.Datefield .fieldBorder,
.Searchnumber .fieldBorder,
.FileField .fieldBorder,
.Numberfield .fieldBorder,
.Combobox .fieldBorder {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.Searchnumber .fieldBorder,
.Searchdate .fieldBorder {
  border-radius: 0;
}

.datepickericontext {
  width: 22px;
  text-align: center;
}

.fieldLabel span {
  margin: 2px;
  font-size: 13px;
}

textarea {
  resize: none;
  overflow: auto;
  height: 60px;
}

.DarkMode .fieldBorder.required {
  filter: invert(100%);
}

.fieldBorder.required {
  background-color: #ffffbf;
}

.entityCombo * {
  font-weight: bold;
  color: var(--higlight-text-color);
  font-size: 15px;
}

.checkboxgroup .chboxtext {
  overflow: auto;
  white-space: -moz-pre-wrap;
  white-space: -pre-wrap;
  white-space: -o-pre-wrap;
  white-space: pre-wrap;
  overflow-wrap: break-word;
  word-wrap: break-word;
  -ms-word-break: break-all;
  word-break: break-word;
  -ms-hyphens: auto;
  -moz-hyphens: auto;
  -webkit-hyphens: auto;
  hyphens: auto;
}

/* Error */
.hasError .fieldBorder,
.hasError .fieldButtonFront,
.hasError .fieldButton {
  border-color: #ff7979;
}

.FieldInner>.Err,
.InnerErrScreen,
.hasError .fieldButton,
.hasError .fieldButtonFront {
  color: #ff7979;
}

.FieldInner>.Err {
  font-weight: bold;
  border-radius: 4px;
  background-color: #ff797924;
}

.FieldInner>.Err>ul {
  margin-block-start: 4px;
  margin-block-end: 4px;
  margin-inline-start: 4px;
  margin-inline-end: 4px;
  padding-inline-start: 20px;
}

.FieldInner>.Err>ul>li {
  padding: 4px 0;
}

.Field .FieldList {
  display: list-item;
  margin-left: 40px;
}

.dropdown:focus,
.fieldBorder:focus,
.fieldButton:focus,
.dummyFocus,
.datepicker div.pbtn.selected,
.fieldButtonFront:focus {
  border: 1px solid var(--higlight-text-color);
}

.dummyFocus {
  border-radius: 4px;
}

.disabled {
  opacity: 0.4;
}

.disabled * {
  /*csak a legfelső elem legyen halvány és ne recurzivan*/
  opacity: 1;
}

.disabled * {
  cursor: not-allowed !important;
}

/* placeholder */
input:placeholder-shown {
  user-select: none;
}

.dropdown:focus,
.fieldBorder:focus {
  transition: border 0.5s ease;
}
</style>