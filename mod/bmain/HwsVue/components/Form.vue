<template>
  <form
    class="Form fit hflex border"
    @keypress.enter="$emit('onEnter', $event)"
    onsubmit="return false"
  >
    <Transition name="maskfade2">
      <div v-if="storeLoading" class="cflex FormSpinner">
        <div class="spin">
          <Icon>f51f</Icon>
        </div>
      </div>
    </Transition>
    <template v-if="showToolbar && topToolbar">
      <div class="noselect toolbar">
        <Button
          v-if="hasFields && (save || imp)"
          @click="onSave"
          :icon="saveicon"
          :title="savetext"
        />
        <Button
          v-if="$root.debug && !isSearch"
          @click="doGenerate"
          icon="f074"
          title="Random adat"
        />
        <Button
          v-if="$root.debug && !isSearch"
          @click="doFormExport"
          icon="f121"
          title="Form export"
        />
        <Button
          v-if="$root.debug && !isSearch"
          @click="doReset"
          title="Kiürítés"
        />
        <Button
          v-if="$root.debug && !isSearch"
          @click="doFillAndSave"
          icon="f7c0"
          title="Feltöltés és mentés"
        />
        <Button v-if="back" @click="onBack" noHighlight title="Mégsem" />
        <slot name="toolbar" />
      </div>
    </template>
    <slot name="formTop" :rowData="rowData" />
    <div class="Body fit">
      <slot :rowData="rowData" name="body" />
      <div
        class="formBody"
        :class="{
          labelLeft: !colsLayout && !topLabels,
          formcols: colsLayout,
          fixWidth: !noFixWidth,
          noFixWidth: noFixWidth,
        }"
      >
        <slot
          v-if="(record_id && rowData && load) || !record_id || !load || !store"
          :rowData="rowData"
        >
          <template v-for="(field, index) in fields" :key="'field_' + index">
            <Field v-bind="field" />
          </template>
        </slot>
        <slot
          v-if="(record_id && rowData && load) || !record_id || !load || !store"
          name="afterFields"
          :rowData="rowData"
        />
        <template v-if="showToolbar && !topToolbar">
          <div class="noselect bottomtoolbar">
            <div class="toolbar hflex">
              <Button
                v-if="hasFields && (save || imp)"
                @click="onSave"
                :icon="saveicon"
                :title="savetext"
                highlight
              />
              <Button
                v-if="$root.debug && !isSearch"
                @click="doGenerate"
                icon="f074"
                title="Random adat"
              />
              <Button
                v-if="$root.debug && !isSearch"
                @click="doReset"
                title="Kiürítés"
              />
              <Button
                v-if="$root.debug && !isSearch"
                @click="doFormExport"
                icon="f121"
                title="Form export"
              />
              <Button
                v-if="$root.debug && !isSearch"
                @click="doFillAndSave"
                icon="f7c0"
                title="Feltöltés és mentés"
              />
              <Button v-if="back" @click="onBack" noHighlight title="Mégsem" />
              <slot name="toolbar" />
            </div>
          </div>
        </template>
      </div>
      <slot name="formEnd" :rowData="rowData" />
    </div>
  </form>
</template>

<script>
export default {
  name: "Form",

  emits: ["onEnter"],

  activated: function () {
    this.tabPanelChange();
  },

  computed: {
    showToolbar: function () {
      if (
        this.save ||
        this.back ||
        (this.$slots.toolbar && this.$slots.toolbar().length)
      )
        return true;
      return false;
    },
  },

  props: {
    record_id: [String, Number],
    save: [Boolean, Function],
    savetext: {
      type: String,
      default: "Mentés",
    },
    saveicon: {
      type: String,
      default: "save",
    },
    imp: [Boolean, Function],
    back: [Boolean, Function],
    store: String,
    fields: Array,
    data: Object,
    extraParams: Object,
    load: Boolean,
    noreset: Boolean,
    loaded: Function,
    saveOkText: String,
    afterSave: Function,
    values: Object,
    askBeforeCreate: String,
    askBeforeUpdate: String,
    isSearch: Boolean,
    openDetailsAfterCreate: Boolean,

    //layout
    noFixWidth: Boolean,
    colsLayout: Boolean,
    topLabels: Boolean,
    topToolbar: Boolean,
  },
  mounted: function () {
    if (this.data) {
      this.formSet(this.data);
      this.rowData = this.data;
    }
    if (this.values) {
      this.rowData = this.values;
      this.formSet(this.values);
    }
    if (this.load) this.doload();
  },
  updated: function () {
    if (this.save || this.imp)
      this.hasFields = this.findFields(null, true).length != 0;
  },
  data: function () {
    return {
      hasFields: true,
      rowData: null,
      storeLoading: false,
    };
  },
  watch: {
    record_id: function (val) {
      if (this.load) this.doload();
    },
    values: function (rowData) {
      this.rowData = rowData;
      this.formSet(rowData);
    },
  },
  methods: {
    doload: function () {
      if (!this.store) {
        this.formReset();
        let g = this.up("Grid"),
          id = this.record_id;
        if (g && g.$attrs.data) {
          let found = g.$attrs.data
            .filter(function (row) {
              return row.id == id;
            })
            .pop();
          if (found) this.formSet(found);
        }
        return;
      }

      this.storeLoading = true;

      if (this.record_id) {
        getStore(this.store).load(this.onStoreLoad, this.record_id, { id: this.record_id });
      } else {
        getStore(this.store).load(this.onStoreLoad);
      }
    },

    reRender: function () {
      this.findFields({
        render: Function,
      }).run("reRender");
    },

    tabPanelChange: function () {
      if (this.load) this.doload();
    },

    onStoreLoad: function (s, data) {
      setTimeout((x) => (this.storeLoading = false), 400); //Animációs effect-
      if (s) {
        var rowData = null;
        this.formReset();
        if (data.data) {
          if (isArray(data.data) && data.data.length) {
            rowData = data.data[0];
          } else {
            rowData = data.data;
          }
        } else {
          if (data[0]) {
            rowData = data[0];
          } else {
            rowData = data;
          }
        }
        this.rowData = rowData;
        this.$nextTick(function () {
          this.formSet(this.rowData);
        });
      }
      if (this.loaded) this.loaded(s, rowData);
    },
    doImport: function () {
      if (!this.formValidate()) return;
      var me = this;
      getStore(this.store).import(this.formGet(), function (s, data) {
        if (s) {
          msg(
            "Sikeres importálás!",
            null,
            null,
            data.import_count
              ? data.import_count + " db rekord lett feldolgozva!"
              : ""
          );
          me.onBack();
        } else {
          msg(
            "Sikertelen importálás!",
            "error",
            null,
            isObject(data)
              ? data.message
              : "Az importált fájl a hibaüzenettekkel kiegészítve letöltődik."
          );
          me.formReset();
        }
      });
    },
    onSave: function (e, fn) {
      if (!this.formValidate()) return;
      var me = this;
      if (e != "ok" && this.askBeforeCreate && empty(this.record_id)) {
        return msg(this.askBeforeCreate, "i/n", function (s) {
          if (s) me.onSave("ok");
        });
      }
      if (e != "ok" && this.askBeforeUpdate && !empty(this.record_id)) {
        return msg(this.askBeforeUpdate, "i/n", function (s) {
          if (s) me.onSave("ok");
        });
      }
      if (this.imp) {
        if (isFunction(this.imp)) {
          this.imp.call(this, this.doImport);
        } else {
          this.doImport();
        }
        return;
      }

      if (isFunction(this.save)) {
        this.save.call(this, this.formGet(), this);
      } else {
        this.storeLoading = true;
        var values = this.formGet();
        if (this.extraParams) values = { ...this.extraParams, ...values };
        if (this.record_id) {
          getStore(this.store).update(
            parseInt(this.record_id),
            values,
            isFunction(fn) ? fn : this.saveCallback
          );
        } else {
          getStore(this.store).create(values, isFunction(fn) ? fn : this.saveCallback);
        }
      }
    },
    saveCallback: function (s, data, code) {
      setTimeout((x) => (this.storeLoading = false), 400); //Animációs effect-
      if (s) {
        if (this.saveOkText) {
          tMsg.s(this.saveOkText);
        } else {
          if (!isFunction(this.back)) tMsg.s("Sikeres mentés!");
        }
        if (!this.load && !this.noreset) this.formReset();
        var goBack = true;
        if (this.openDetailsAfterCreate && !this.record_id) {
          var id = data.id;
          if (!id && data.data) id = data.data.id;
          if (id) {
            this.up("Grid").toDetails(id);
            return;
          }
        }
        if (this.afterSave) {
          // ha van record_id akkor update, ha nincs akkor create
          goBack = this.afterSave(data, this.record_id, this.onBack) !== false;
        }
        if (goBack) this.onBack();
      } else {
        if (code === 422) {
          this.formSetErrors(data);
        }
      }
      //részletek ablak újratöltése
      var detail = this.up("Detail");
      var grid = this.up("Grid"); //ha gritd belsejében található form
      if (detail && !grid) {
        detail.refresh();
      }
    },
    onBack: function () {
      if (isFunction(this.back)) {
        this.back.call(this);
      }
    },
    doGenerate: function () {
      TestData.tmComboData = {};
      FormFill.fillData(this);
    },
    doReset: function () {
      TestData.clearForm(this);
    },
    doFillAndSave: function () {
      TestData.tmComboData = {};
      FormFill.fillandSave(this);
    },
    doFormExport: function () {
      FormFill.doFormExport.call(this);
    },
  },
};
</script>

<style>
.Form {
  position: relative;
}
.formBody {
  position: relative;
  min-height: 30px;
  min-width: 100px;
}

/* .formBody.fixWidth .bottomtoolbar, */
/* .formBody.fixWidth .intervalFieldCt, */
.formBody.fixWidth .fieldbody {
  width: 400px;
}

.formBody .intervalFieldCt .fieldbody {
  width: initial;
  margin: 0;
}

.formBody.formcols > .Field {
  float: left;
  margin-right: 4px;
}

.Form > .Body {
  padding: 10px;
}

.Form > .toolbar {
  padding: 10px 10px 0 10px;
}

.formBody > .toolbar:first-child {
  padding-bottom: 10px;
}

.formBody > .toolbar:last-child {
  padding-bottom: 10px;
}

.bottomtoolbar {
  position: -webkit-sticky;
  position: sticky;
  bottom: 20px;
}

.bottomtoolbar > .toolbar {
  position: absolute;
  right: 0;
  padding: 4px;
  /* bottom: 0; */
}

.formBody.labelLeft .PanelBody,
.formBody.labelLeft {
  display: table;
}

.formBody.noFixWidth .PanelBody,
.formBody.noFixWidth {
  width: 100%;
}

.formBody.labelLeft .bottomtoolbar,
.formBody.labelLeft .Field {
  display: table-row;
}

.formBody.labelLeft .intervalFieldCt .Field {
  display: initial;
}

.formBody.labelLeft .Field > .fieldLabel {
  padding-right: 10px;
}

.formBody.labelLeft .Field > .fieldLabel,
.formBody.labelLeft .Field > .FieldInner {
  display: table-cell;
  min-width: 100px;
}

.formBody.labelLeft .intervalFieldCt .Field > .fieldLabel,
.formBody.labelLeft .intervalFieldCt .Field > .FieldInner {
  display: initial;
  min-width: initial;
}

.formBody.labelLeft .Field > .fieldLabel {
  vertical-align: top;
  padding-top: 6px;
  padding-bottom: 6px;
}

.FormSpinner {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  background-color: #f1f1f1;
  z-index: 1;
}
.FormSpinner .icon {
  font-size: 70px;
  color: var(--higlight-text-color);
}
</style>