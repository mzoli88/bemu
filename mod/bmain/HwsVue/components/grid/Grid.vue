<template>
  <div class="Grid border vflex fit">
    <template v-if="show == 'create'">
      <slot name="formCreate" :save="true" :back="toList" :store="store" :record_id="selected_id"
        :askBeforeCreate="askBeforeCreate" :afterSave="afterSave" :fields="listCreate"
        :openDetailsAfterCreate="openDetailsAfterCreate">
      </slot>
      <Panel title="Felvitel" border h v-if="!hasSlot('formCreate')">
        <Form save :store="store" :back="toList" :fields="hasSlot('pureCreate') ? null : listCreate"
          :askBeforeCreate="askBeforeCreate" :afterSave="afterSave" :openDetailsAfterCreate="openDetailsAfterCreate">
          <template #afterFields>
            <slot name="create" :back="toList" :store="store" :record_id="selected_id">
            </slot>
            <slot name="pureCreate" :back="toList" :store="store" :record_id="selected_id">
            </slot>
          </template>
        </Form>
      </Panel>
    </template>

    <template v-if="show == 'update'">
      <slot :save="true" :load="true" name="formUpdate" :back="toList" :store="store" :record_id="selected_id"
        :askBeforeCreate="askBeforeCreate" :afterSave="afterSave" :fields="listCreate">
      </slot>
      <Panel title="Módosítás" border h v-if="!hasSlot('formUpdate')">
        <Form save load :back="toList" :store="store" :record_id="selected_id"
          :fields="hasSlot('pureUpdate') ? null : listUpdate" :askBeforeUpdate="askBeforeUpdate" :afterSave="afterSave">
          <template #afterFields="udata">
            <slot name="update" :back="toList" :store="store" :record_id="selected_id" :rowData="udata.rowData">
            </slot>
            <slot name="pureUpdate" :back="toList" :store="store" :record_id="selected_id" :rowData="udata.rowData">
            </slot>
          </template>
        </Form>
      </Panel>
    </template>

    <template v-if="cRoutes && cRoutes[show]">
      <slot :name="show" :save="true" :store="store" :back="toList" :fields="listCreate" />
    </template>
    <template v-if="uRoutes && uRoutes[show]">
      <slot :name="show" :save="true" :load="true" :back="toList" :store="store" :record_id="selected_id"
        :fields="listUpdate" />
    </template>

    <Detail v-if="show == 'details'" :store="store" :fields="listDetails" :record_id="selected_id" :uRoutes="uRoutes"
      :title="titleDetaile || title">
      <template v-slot:default="d">
        <slot v-bind="d" name="details" />
      </template>
      <template v-slot:detailtoolbar="d">
        <slot v-bind:rowData="d.rowData" name="detailtoolbar" />
      </template>
      <template v-slot:detailrowBtn="d">
        <slot v-bind:rowData="d.rowData" name="detailrowBtn" />
      </template>
      <template v-slot:detailformSlot="d">
        <slot v-bind="d" name="detailformSlot" />
      </template>
    </Detail>

    <Panel v-if="show == 'import'" :title="importBtnName ? importBtnName : 'Importálás'" border h>
      <ImportCmp :store="store" />
    </Panel>

    <div h v-show="show == 'list'" class="GridBodyPanel hflex fit">
      <Pager :noPage="noPager" :store="store" :load="load">
        <div class="vflex fit toolbar">
          <div v-if="entity" class="toolbar cflex">
            <Field type="entity" />
          </div>
          <div class="toolbar cflex">
            <Button v-if="canCreate()" @click="goTo('create')" title="Felvitel" />
            <template v-if="cRoutes">
              <template v-for="(route, index) in cRoutes">
                <Button v-if="ifroute(null, route)" :class="index" :key="index" :icon="route.icon" :title="route.title"
                  :quequeDisable="route.quequeDisable" @click="
      route.click ? route.click($event, _self) : goTo(index)
      " />
              </template>
            </template>
            <Button v-if="checkboxDelete" @click="onCheckboxDelete" icon="delete" title="Kijelölt sorok törlése" />
            <Button v-if="isExport" quequeDisable @click="onExport" icon="export" title="Export" />
            <Button v-if="isImport" @click="onImport" quequeDisable icon="import"
              :title="importBtnName ? importBtnName : 'Import'" />
            <slot name="bottomToolbar" />
            <div v-if="checkbox" class="cflex">
              Kijelölt sorok száma: {{ chboxSelectedNumber }} db
            </div>
            <Queque />
          </div>
          <div class="Grid_title fit cflex" v-if="title">{{ title }}</div>
        </div>
      </Pager>

      <FastFilterCmp ref="fastFilter" :data="fastFilter" :fastFilterValue="fastFilterValue"
        v-if="fastFilter && showGrid" v-show="!MajaxManager.loading" />
      <slot name="infobox" />

      <div v-if="MajaxManager.loading" class="vflex fit GridMask">
        <Transition name="maskfade2">
          <div class="fit cflex GridSpinner">
            <div class="spin">
              <Icon>f51f</Icon>
            </div>
          </div>
        </Transition>
      </div>

      <div v-show="!MajaxManager.loading" class="gridBodySpacer"></div>
      <div v-if="showGrid" v-show="!MajaxManager.loading" class="GridBodyParent Body fit vflex">
        <div class="GridBody fit">
          <table ref="table">
            <thead class="headerRow noselect" @contextmenu="onHeaderContextmenu">
              <tr>
                <th v-if="checkbox">
                  <div class="GridHeader titleBorder gridCheckbox cflex" v-if="!MajaxManager.loading">
                    <Field type="checkbox" ref="checkboxAll" @change="checkboxAll" center :title="onCheckboxTitle()" />
                  </div>
                </th>
                <th v-if="hasTools ||
      uRoutes ||
      isUpdate ||
      isDelete ||
      lock ||
      isDownload
      ">
                  <div class="GridHeader titleBorder">
                    <span v-if="operationsText">{{ operationsText }}</span>
                    <span v-else>Műveletek</span>
                  </div>
                </th>
                <template v-for="colData in listableHeaders">
                  <th v-bind:key="'header_' + colData.name" @click="doSort(colData)" :class="[
      colData.name,
      colData.sortable != false ? 'pointer' : '',
      sortData.property == colData.name ? 'sorted' : '',
    ]" v-if="!hiddenHeaders.includes(colData.name)">
                    <div class="GridHeader titleBorder" v-tooltip>
                      <div class="GridHeaderText">
                        {{ colData.title || colData.name }}
                      </div>
                      <div v-if="sortData.property == colData.name" class="icon sort GridHeadericon cflex"
                        :class="{ asc: sortData.asc, desc: !sortData.asc }"></div>
                    </div>
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <template v-for="(item, index) in listData" v-bind:key="'row_' + index">
                <tr class="GridRow" :class="onRowClass(item, index)">
                  <td v-if="checkbox">
                    <div class="gridCheckbox cflex" v-if="!MajaxManager.loading">
                      <Field type="checkbox" :name="'ch_' + item.id" gchbox @change="checkedChange(item.id, $event)"
                        v-if="onCheckboxIf(item)" :title="onCheckboxTitle(item)"
                        :startValue="CheckboxSelected[item.id] ? 'I' : 'N'" />
                    </div>
                  </td>
                  <td v-if="hasTools ||
      uRoutes ||
      isUpdate ||
      isDelete ||
      lock ||
      isDownload
      " :class="{
      pointer: smallDetaile,
      selected: row_selected_index == index,
    }">
                    <div class="GridTools toolbar">
                      <Button v-if="canDetaile(item)" icon="details" title="Részletek megtekintése"
                        @click="goTo('details', item)" />
                      <Button v-if="canUpdate(item)" icon="edit" title="Módosítás" @click="goTo('update', item)" />
                      <Button v-if="isDownload" title="Letöltés" icon="f019" @click="download(item)" />
                      <template v-if="uRoutes">
                        <template v-for="(route, index) in uRoutes">
                          <Button v-if="ifroute(item, route)" :class="index" :key="index"
                            :quequeDisable="route.quequeDisable" :icon="route.icon" :title="route.title" @click="
      route.click
        ? route.click($event, _self, item)
        : goTo(index, item)
      " />
                        </template>
                      </template>
                      <slot v-bind="item" name="rowBtn" />
                      <Button v-if="canRowDel ? canRowDel(item) : isDelete" icon="delete" title="Törlés" conf
                        @click="onDelete(item)" />
                      <Button class="DetaileDownButton cflex" v-if="smallDetaile && !smallDetailsOpen"
                        :key="'dsBtn' + row_selected_index + '_' + index" :icon="row_selected_index == index
        ? 'sDetailRight'
        : 'sDetailDown'
      " @click="rowClick(index)" />
                      <div v-if="lock && item.LockData" class="cflex LockButton pointer"
                        :title="'Rekord zárolva: ' + item.LockData">
                        <Icon>f071</Icon>
                      </div>
                      <slot v-bind="item" name="rowBtnEnd" />
                    </div>
                  </td>
                  <template v-for="colData in listableHeaders">
                    <td v-bind:key="'row_' + index + 'col_' + colData.name"
                      :style="{ textAlign: colData.align || 'left' }" :class="[
      colData.name,
      smallDetaile && !smallDetailsOpen ? 'pointer' : null,
      row_selected_index == index ? 'selected' : null,
    ]" v-if="!hiddenHeaders.includes(colData.name)" @click="rowClick(index)">
                      <div class="GridCelContent" :class="{ maxCellWidth: !noMaxCellWidth }" v-tooltip>
                        <slot v-bind="item" :name="colData.name">
                          <span v-html="renderColValue(colData, item[colData.name], item)
      ">
                          </span>
                        </slot>
                      </div>
                    </td>
                  </template>
                </tr>
                <template v-if="smallDetaile">
                  <tr class="GridSmallDetail" :class="onRowClass(item, index)" :key="'rowdetaile_' + index"
                    v-if="row_selected_index == index || smallDetailsOpen">
                    <td colspan="100%">
                      <div class="GridSmallDetailCt">
                        <Form class="SmallDetailForm" v-if="listSmallDetails.length" :fields="listSmallDetails"
                          :data="item" :colsLayout="!smallDetailsNocols" noFixWidth />
                        <slot name="smallDetaile" v-bind="item" />
                      </div>
                    </td>
                  </tr>
                </template>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <SearchPanel v-if="!noSearch && showGrid" v-show="show == 'list'" :store="store" :fields="listSearch"
      :defaultSearch="defaultSearchValue" ref="SearchPanel" :collapsed="!searchOpen" />
    <Teleport to="body" v-if="showHeaderContextmenu">
      <div class="gridHeadContextMenu frame" ref="HeaderContextmenu">
        <div v-for="colData in listableHeaders" :key="colData.name">
          <div class="toolbar" v-if="colData.hideable != false">
            <Field type="checkbox" :name="colData.name" :ref="'ContextHeaderField_' + colData.name"
              :startValue="hiddenHeaders.includes(colData.name) ? 'N' : 'I'"
              @change="(v) => ongridHeadContextMenuChange(v, colData.name)" />
            <div class="cflex">{{ colData.title }}</div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import SearchPanel from "./SearchPanel.vue";
import FastFilterCmp from "./FastFilter.vue";
import ImportCmp from "./Import.vue";
import Queque from "./../Queque.vue";
export default {
  name: "Grid",
  props: {
    sd: Boolean, //smallDetaile

    store: String,
    cols: {
      type: Object,
      default: function () {
        return { empty: true };
      },
    },
    extracols: {
      type: Object,
      default: function () {
        return { empty: true };
      },
    },
    noPager: Boolean,
    noSearch: Boolean,
    searchOpen: Boolean,
    create: Boolean,
    createJog: Array,
    update: Boolean,
    updateJog: Array,
    canRowUpdate: Function,
    canRowDetaile: Function,
    setRowClass: Function,
    del: [Boolean, String],
    canRowDel: Function,
    defaultSearch: Object,
    smallDetaile: {
      type: Boolean,
      default: (x) => x.sd,
    },
    smallDetailsNocols: Boolean,
    smallDetailsOpen: Boolean,
    imp: [Boolean, Function],
    importBtnName: String,
    exp: Boolean,
    desc: Boolean,
    entity: Boolean,
    sort: String,
    checkboxDelete: Boolean,
    checkbox: Boolean,
    checkboxIf: Function,
    checkboxTitle: [Function, String],
    afterSave: Function,
    cRoutes: Object,
    uRoutes: Object,
    fastFilter: Object,
    fastFilterStart: String,
    fastFilterDefaultSearch: Object,
    askBeforeUpdate: String,
    askBeforeCreate: String,
    parent: String,
    operationsText: String, //Művelet megnevezés helyett lehet másik szöveget megadni
    extraParams: Object,

    openDetailsAfterCreate: Boolean,

    title: String,
    titleDetaile: String,
    status: Boolean,

    noMaxCellWidth: Boolean,
  },
  created: function () {
    this.makeHash();
  },
  data: function () {
    let hash = this.getCpHash(),
      show = "list",
      fastFilterValue = null,
      selected_id = null;
    // dd("gridgash", hash);
    if (hash) {
      hash = hash.split("|");
      show = hash.shift();
      if (show == "details" || show == "update") selected_id = hash.shift();
      if (show == "list" && this.fastFilter) fastFilterValue = hash.shift();
      if (hash.length && this.uRoutes[show]) selected_id = hash.shift();
    }
    // dd("fastFilterValue", fastFilterValue);
    if (show == "list" && this.fastFilter && empty(fastFilterValue)) {
      fastFilterValue = Object.keys(this.fastFilter)[0];
      getStore(this.store).fixQuery("fastFilter", fastFilterValue);
    }

    // dd("gridlist", fastFilterValue, show);

    var Searchvals = {};

    if (this.status) Searchvals.status = "I";
    if (this.defaultSearch)
      Searchvals = { ...this.defaultSearch, ...Searchvals };

    return {
      show: show,
      hasTools:
        this.hasSlot(
          "update",
          "pureUpdate",
          "formUpdate",
          "details",
          "rowBtn"
        ) ||
        this.smallDetaile ||
        false,
      MajaxManager: MajaxManager,

      headers: [],
      selected_id: selected_id,
      rowData: null,
      row_selected_index: null,
      sortData: {
        property: this.sort || null,
        asc: this.desc ? false : true,
      },
      listData: [],
      chboxSelectedNumber: 0,
      CheckboxSelected: {},
      fastFilterValue: fastFilterValue,
      defaultSearchValue: Searchvals,

      showHeaderContextmenu: false,
      hiddenHeaders: [],

      isCreate: this.create,
      isUpdate: this.update,
      isDelete: this.del,
      isExport: this.exp,
      isImport: this.imp,
      isDownload: false,
      lock: false,
      useStatus: this.status,
    };
  },
  computed: {
    showGrid: function () {
      return (
        !this.entity ||
        empty(this.$root.entity) ||
        (this.entity && this.$root.entity && this.$root.entity.active)
      );
    },
    listableHeaders: function () {
      return this.headers.filter(function (header) {
        return (
          header.title &&
          header.list != false &&
          (header.list === true || header.smallDetaile != true)
        );
      });
    },
    listSearch: function () {
      return this.computeHeaders(this.headers, "search");
    },
    listCreate: function () {
      return this.computeHeaders(this.headers, "create");
    },
    listUpdate: function () {
      return this.computeHeaders(this.headers, "update");
    },
    listDetails: function () {
      return this.computeHeaders(this.headers, "details");
    },
    listSmallDetails: function () {
      return this.computeHeaders(this.headers, "smallDetaile");
    },
  },

  mounted: function () {
    if (this.store) {
      var st = getStore(this.store).onLoad(this.onStoreLoad, this);
      this.getFastFilterActive();
      this.$nextTick(function () {
        if (this.defaultSearchValue) st.search(this.defaultSearchValue);
        this.load();
      });
    } else {
      this.onStoreLoad(true, this.$attrs);
    }
    this.doLock();
  },
  beforeUnmount: function () {
    clearTimeout(this.lockTimeout);
    if (this.lock && this.show != "list") {
      getStore(this.store).load(this.lastSelected, {
        doModelLockOpen: true,
      });
    }
  },
  activated: function () {
    this.tabPanelChange();
  },
  watch: {
    show: function (val) {
      this.makeHash();
      this.doLock();
      if (val == "list") {
        if (this.lock) {
          clearTimeout(this.lockTimeout);
          getStore(this.store).load(this.lastSelected, {
            doModelLockOpen: true,
          });
        }
      }
    },
    selected_id: function (val) {
      this.makeHash();
    },
    fastFilterValue: function (val) {
      if (this.$refs.SearchPanel) this.$refs.SearchPanel.refreshSearchPanel();
      if (this.fastFilterDefaultSearch && this.fastFilterDefaultSearch[val]) {
        this.defaultSearchValue = this.fastFilterDefaultSearch[val];
      } else {
        this.defaultSearchValue = this.defaultSearch;
      }
      this.makeHash();
      if (this.$refs.fastFilter) this.$refs.fastFilter.active = val;
      getStore(this.store).fixQuery("fastFilter", val).page(1);
      this.load();
    },
    defaultSearchValue: function (val) {
      getStore(this.store).search(val);
    },
  },
  methods: {
    getFastFilterActive: function () {
      if (this.fastFilter) {
        var d = this.getCpHash();
        if (d) {
          d = d.split("|");
          if (d[0] == "list") {
            this.fastFilterValue = d[1];
            getStore(this.store).fixQuery("fastFilter", d[1]);
          }
        }
      }
    },
    makeHash: function () {
      // dd("makeHash", this.show,this.halndleOldhash);
      switch (this.show) {
        case "list":
          this.setHash(
            this.show + (this.fastFilterValue ? "|" + this.fastFilterValue : "")
          );
          break;
        case "create":
        case "import":
          this.setHash(this.show);
          break;
        default:
          this.setHash(
            this.show + (this.selected_id ? "|" + this.selected_id : "")
          );
          break;
      }
    },
    load: function (extraparams) {
      if (!this.store) return false;
      // if (!this.showGrid) return false; //HA entitás nincs kiválasztva akkor ez megakadályozta a betöltést
      var st = getStore(this.store);
      if (!empty(this.sortData.property))
        st.sort(this.sortData.property, this.sortData.asc);
      extraparams = extraparams || {};
      if (this.parent) {
        let detail = this.up("Detail");
        if (detail) {
          st.fixQuery(this.parent, detail.record_id);
        }
      }
      if (!this.firstLoaded) {
        extraparams.getMeta = 1;
      }
      if (this.extraParams)
        extraparams = { ...extraparams, ...this.extraParams };
      st.load(extraparams);
    },
    tabPanelChange: function () {
      // dd("tabPanelChange", this.show);
      // this.show = "list";
      this.makeHash();
      if (this.show == "list") {
        this.getFastFilterActive();
        if (this.firstLoaded) this.load();
      }
    },
    computeHeaders: function (headers, type) {
      var me = this;
      if (this.entity && !empty(this.$root.entity)) {
        let render = function (e) {
          return me.$root.getEntitiyName(e);
        };
        headers = [
          ...[
            {
              list: false,
              title: "Entitás",
              search: false,
              name: "entity_id",
              smallDetaile: false,
              details: {
                type: "display",
                render: render,
              },
              update: {
                type: "display",
                render: render,
              },
              create: {
                type: "entity",
              },
            },
          ],
          ...headers,
        ];
      }
      return (
        headers
          .filter(function (header) {
            switch (type) {
              case "search":
                return header.title && header.search;
              case "create":
                if (isObject(header.create))
                  return header.title || header.create.label;
                if (isObject(header.cu)) return header.title || header.cu.label;
                return header.title && header.create && header.cu != false;
              case "update":
                if (isObject(header.update))
                  return header.title || header.update.label;
                if (isObject(header.cu)) return header.title || header.cu.label;
                return header.title && header.update && header.cu != false;
              case "details":
                return header.title && header.details !== false;
              case "smallDetaile":
                return (
                  header.title &&
                  (header.smallDetaile === true ||
                    (header.smallDetaile !== false && header.list === false))
                );
              default:
                return false;
            }
          })
          .map(function (header, i) {
            var out = {
              name: header.name,
              label: header.title,
              store: header.store,
              boxes: header.boxes,
              inputAlign: header.align,
              type: header.type,
              sort: header.sort || i,
              sort2: i,
            };

            switch (type) {
              case "search":
                if (header.fastFilter) out.fastFilter = header.fastFilter;
                if (isObject(header.search)) {
                  out = { ...out, ...header.search };
                } else {
                  out.type = header.search;
                }
                break;
              case "create":
                if (header.if) out.if = header.if;
                if (header.required) out.required = header.required;
                if (header.startValue) out.startValue = header.startValue;
                if (header.validation) out.validation = header.validation;
                if (header.vReg) out.vReg = header.vReg;
                if (header.vRegText) out.vRegText = header.vRegText;
                if (header.info) out.info = header.info;
                if (header.warning) out.warning = header.warning;

                if (isObject(header.cu)) {
                  out = { ...out, ...header.cu };
                } else if (isString(header.cu)) {
                  out.type = header.cu;
                } else if (isObject(header.create)) {
                  out = { ...out, ...header.create };
                } else {
                  out.type = header.create;
                }
                break;
              case "update":
                if (header.if) out.if = header.if;
                if (header.required) out.required = header.required;
                if (header.startValue) out.startValue = header.startValue;
                if (header.validation) out.validation = header.validation;
                if (header.vReg) out.vReg = header.vReg;
                if (header.vRegText) out.vRegText = header.vRegText;
                if (header.info) out.info = header.info;
                if (header.warning) out.warning = header.warning;

                if (isObject(header.cu)) {
                  out = { ...out, ...header.cu };
                } else if (isString(header.cu)) {
                  out.type = header.cu;
                } else if (isObject(header.update)) {
                  out = { ...out, ...header.update };
                } else {
                  out.type = header.update;
                }
                break;
              case "smallDetaile":
                if (isFunction(header.smallDetaile))
                  header.render = header.smallDetaile;
                out.type = "display";
                out.render = header.render;
                if (header.evalSmallDetaile)
                  out.hasRowData = header.evalSmallDetaile;

                break;
              case "details":
                if (isFunction(header.details)) header.render = header.details;
                out.type = "display";
                if (header.render) {
                  out.render = header.render;
                } else {
                  if (isString(header.list)) {
                    out.render = function (v, a, r) {
                      return r[header.list];
                    };
                  }
                }
                if (isObject(header.details)) {
                  out = { ...out, ...header.details };
                }

                if (header.evalDetails) out.hasRowData = header.evalDetails;

                break;
              default:
                return false;
            }
            if (out.type === true) out.type = header.type;
            return out;
          })
          // .sort((a, b) => (a.sort > b.sort ? 1 : -1));
          .sort((a, b) =>
            a.sort > b.sort
              ? 1
              : a.sort === b.sort
                ? a.sort2 > b.sort2
                  ? 1
                  : -1
                : -1
          )
      );
    },
    gfr: function () {
      for (var i in arguments) {
        let data = arguments[i];
        if (!empty(data)) return data;
      }
    },
    metaColset: function (i, meta, cols) {
      meta = meta || {};

      if (this.extracols[i] == false) {
        return false;
      }

      var extra = this.extracols[i] || {};
      cols = cols || this.cols[i] || {};

      var h = { ...meta, ...extra, ...cols };

      h.name = i;

      if (i == "id") {
        h.list = this.gfr(h.list, false);
        h.smallDetaile = this.gfr(h.smallDetaile, false);
        h.create = this.gfr(h.create, false);
        h.update = this.gfr(h.update, false);
        h.search = this.gfr(h.search, false);
      }

      if (i == "status" || i == "statusz") {
        h.update = this.gfr(h.update == true ? null : h.update, "rstatus");
        h.create = this.gfr(h.create == true ? null : h.create, "rstatus");
        h.search = this.gfr(h.search == true ? null : h.search, "cstatus");
      }

      if (h.create == "rstatus") {
        h.create = {
          type: "rstatus",
          startValue: "I",
        };
      }

      h.type = this.gfr(h.type, "string");
      h.create = this.gfr(h.create, h.title ? h.type : false);
      h.update = this.gfr(h.update, h.title ? h.type : false);

      if (!empty(h.type)) {
        switch (h.type) {
          case "float":
          case "double":
          case "decimal":
          case "integer":
            // h.align = this.gfr(h.align, "right");
            h.search = this.gfr(h.search, "searchnumber");
            break;
          case "date":
            h.search = this.gfr(h.search, "searchdate");
            break;
          default:
            break;
        }
      }

      h.search = this.gfr(h.search, h.title ? h.type : false);
      h.validation = this.gfr(h.validation, h.validation);
      h.render = this.gfr(h.render, h.render);

      return h;
    },
    onStoreLoad: function (s, r) {
      if (this.checkbox) {
        this.findFields({
          gchbox: isString,
        }).setValue();
      }
      this.row_selected_index = null;
      if (s) {
        this.firstLoaded = true;
        if (r.meta) {
          this.headers = [];
          this.hiddenHeaders = [];

          if (r.hasOwnProperty("lock")) {
            this.lock = true;
            this.doLock();
          }
          if (r.hasOwnProperty("create")) this.isCreate = r.create;
          if (r.hasOwnProperty("update")) this.isUpdate = r.update;
          if (r.hasOwnProperty("delete")) this.isDelete = r.delete;
          if (r.hasOwnProperty("export")) this.isExport = r.export;
          if (r.hasOwnProperty("import")) this.isImport = r.import;
          if (r.hasOwnProperty("useStatus")) this.useStatus = r.useStatus;
          if (r.hasOwnProperty("download")) this.isDownload = r.download;
          if (r.hasOwnProperty("sort")) this.sortData.property = r.sort;
          if (r.hasOwnProperty("asc")) this.sortData.asc = r.asc;
          if (r.hasOwnProperty("defaultSearch"))
            this.defaultSearchValue = r.defaultSearch;

          if (this.useStatus) this.defaultSearchValue.status = "I";
          if (!this.cols.empty) {
            for (var i in this.cols) {
              this.headers.push(this.metaColset(i, r.meta[i]));
            }
          } else if (r.hasOwnProperty("cols") && !empty(r.cols)) {
            for (var i in r.cols) {
              this.headers.push(this.metaColset(i, r.meta[i], r.cols[i]));
            }
          } else {
            for (var i in r.meta) {
              this.headers.push(this.metaColset(i, r.meta[i]));
            }
          }

          //hide==true mezők keresése
          this.hiddenHeaders = this.headers
            .filter((x) => x.hide == true)
            .map((x) => x.name);
        } else {
          if (empty(this.headers) && this.cols) {
            for (var i in this.cols) {
              this.headers.push(this.metaColset(i));
            }
          }
        }

        if (r.data) {
          //rákényszerítjük, hogy ürítse a listát és a nextTick segítségével újra generáljuk a templatet.
          this.listData = [];
          this.$nextTick(function () {
            // st.load(extraparams);
            this.listData = r.data;
            // this.$set(this, "listData", r.data);
          });
        } else {
          if (r.reload && this.show == "list") {
            this.$nextTick((x) => this.load());
          }
        }
      }
      this.$nextTick(() => {
        this.checkboxIsAllChecked();
      });
    },
    rowClick: function (id) {
      if (this.smallDetaile) {
        if (this.row_selected_index == id) {
          this.row_selected_index = null;
        } else {
          this.row_selected_index = id;
        }
      }
    },
    doSort: function (colData) {
      if (colData.sortable == false) return;
      if (colData.name == this.sortData.property) {
        this.sortData.asc = !this.sortData.asc;
      } else {
        this.sortData.property = colData.name;
        this.sortData.asc = true;
      }
      this.load();
    },
    renderColValue: function (colData, value, rowData) {
      // if (this.useStatus && colData.name == "status") {
      //   return value == "I" || value == "Aktív" ? "Aktív" : "Inaktív";
      // }
      if (isString(colData.list)) return rowData[colData.list];
      if (colData.render) {
        value = colData.render(value, colData.name, rowData);
      }
      if (
        (colData.type == "number" || colData.type == "integer") &&
        isNumber(value)
      )
        value = value.toString().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1 ");
      return value;
    },
    onDelete: function (data, fromDeatail) {
      var me = this;
      getStore(me.store).delete(data.id, function (s) {
        if (s && fromDeatail) {
          me.toList();
          return;
        }
        var detail = me.up("Detail");
        if (detail) detail.refresh();
        me.load();
      });
    },
    toList: function () {
      if (this.fromDeatail) {
        this.goTo("details", this.fromDeatail);
        this.fromDeatail = false;
        return;
      }

      if (this.halndleOldhash) {
        // dd (this.halndleOldhash);
        //részletek ablak bezárásakor navigáljon vissza a régi hash-re
        setHash(this.halndleOldhash);
        this.halndleOldhash = null;
        this.selected_id = null;
        this.show = "list";
      } else {
        this.selected_id = null;
        this.show = "list";
        var detail = this.up("Detail");
        if (detail) detail.refresh();

        this.$nextTick(() => this.load()); //később fusson le mint az unlock
      }
    },
    toDetails: function (id) {
      this.selected_id = id;
      this.goTo("details");
    },
    onExport: function () {
      var me = this,
        store = getStore(me.store);

      if (store.$displayData.total > 1000000)
        return msg(
          "Egymillió rekord szám fölött nem lehet exportálni!",
          "warn"
        );
      store.export({ hiddenHeaders: me.hiddenHeaders });
    },
    onImport: function () {
      this.show = "import";
    },
    onCheckboxIf: function (item) {
      if (this.checkboxIf) return this.checkboxIf(item);
      return true;
    },
    onCheckboxTitle: function (item) {
      if (isString(this.checkboxTitle)) return this.checkboxTitle;
      if (this.checkboxTitle) return this.checkboxTitle(item);
      return;
    },

    checkboxIsAllChecked: function () {
      if (!this.checkbox) return;

      this.$nextTick(() => {
        var chbox = this.findFields({
          gchbox: isString,
        });
        if (chbox.length == 0) return;
        var notselected = chbox.filter((x) => x.getValue() != "I");
        // dd(notselected.length, notselected.length > 0 ? "N" : "I");
        var newValue = notselected.length > 0 ? "N" : "I";
        if (newValue != this.$refs.checkboxAll.getValue()) {
          this.noCheckAll = true;
          this.$refs.checkboxAll.setValue(newValue);
        }
      });
    },

    checkboxAll: function (val) {
      if (this.noCheckAll) {
        this.noCheckAll = false;
        return;
      }
      this.findFields({
        gchbox: isString,
      }).setValue(val);
    },
    clearCheckboxSelected: function () {
      this.CheckboxSelected = {};
      this.chboxSelectedNumber = 0;
    },
    getCheckboxSelected: function () {
      return Object.keys(this.CheckboxSelected).map((x) => parseInt(x));
    },
    onCheckboxDelete: function () {
      var ids = this.getCheckboxSelected();
      if (ids.length) {
        ask("Biztosan törölni szeretné a kijelölt sorokat?", () => {
          getStore(this.store).delete(
            {
              ids: ids,
            },
            (s) => {
              if (!s) return;
              getStore(this.store).load();
              this.clearCheckboxSelected();
            }
          );
        });
      }
    },
    checkedChange: function (id, val) {
      if (val == "I") {
        this.CheckboxSelected[id] = true;
      } else {
        delete this.CheckboxSelected[id];
      }
      this.chboxSelectedNumber = this.getCheckboxSelected().length;
      this.checkboxIsAllChecked();
    },
    onLockCallback: function (s, d) {
      // dd(s, "callback");
      this.lastSelected = this.selected_id;
      if (!s) this.goTo("list");
    },
    doLock: function () {
      clearTimeout(this.lockTimeout);
      var me = this;
      if (this.selected_id) {
        this.lastSelected = this.selected_id;
      }
      if (this.lock) {
        if (!this.LockOnView) {
          me.LockOnView = me;
          getStore(me.store).onView(me.onLockCallback, me);
        }

        if (
          this.store &&
          !(
            this.show == "list" ||
            this.show == "create" ||
            (this.cRoutes && this.cRoutes[this.show])
          ) &&
          this.selected_id
        ) {
          this.lockTimeout = setTimeout(function () {
            if (!me.isMounted || !me.isActivated) return;
            getStore(me.store).load("hide", me.selected_id);
            me.doLock();
          }, 60 * 1000);
        }
      }
    },
    goTo: function (route, rowData, fromDeatail) {
      if (rowData) {
        this.selected_id = rowData.id;
        this.rowData = rowData;
      }
      this.show = route;
      if (fromDeatail) this.fromDeatail = rowData;
    },
    $hash: function (hash, isManualHashChange) {
      // dd("grid$hash", hash);
      if (!hash) return;
      var tmp = hash.split("|");

      if (tmp[0] != "list" && isManualHashChange) {
        //ha link segítségével jutunk el a részletek abba vagy módosításba (stb), akkor bezárással az előző képernyőre térjen vissza
        let parenthCount = this.$getParentHashCount() + 1;
        let W_newhash = this.$getHash().splice(0, parenthCount).join('/').replace(/(details\|)[0-9]*$/, 'details');
        //join('/').split('/') azért kell, mert az isManualHashChange -ne írjuk felül a splice-al mert akkor rossz lesz a végeredmény
        let W_oldhash = isManualHashChange.join('/').split('/').splice(0, parenthCount).join('/').replace(/(details\|)[0-9]*$/, 'details');
        // dd('gridOldhash',isManualHashChange,W_oldhash,W_newhash,W_newhash != W_oldhash);
        //ha a régi és új megegyezik (pl worklfow link egyik részletekről a másikra, akkor be leherssen zárni az ablakot)
        if (W_newhash != W_oldhash) this.halndleOldhash = isManualHashChange;
      }
      switch (tmp[0]) {
        case "list":
          this.show = tmp[0];
          if (this.fastFilter) {
            this.fastFilterValue = tmp[1];
            if (this.fastFilter && !this.fastFilterValue) {
              this.fastFilterValue =
                this.fastFilterStart || Object.keys(this.fastFilter)[0];
              getStore(this.store).fixQuery("fastFilter", this.fastFilterValue);
            }
          }
          break;
        case "update":
          this.selected_id = tmp[1];
          this.show = "update";
          break;
        case "details":
          if (this.hasSlot("details")) {
            this.show = tmp[0];
            this.selected_id = tmp[1];
          } else {
            this.show = "list";
          }
          break;
        case "create":
          this.show = "create";
          break;
        case "import":
          this.show = "import";
          break;
        default:
          if (this.cRoutes && this.cRoutes.hasOwnProperty(tmp[0])) {
            this.goTo(tmp[0]);
            break;
          }
          if (this.uRoutes && this.uRoutes.hasOwnProperty(tmp[0])) {
            this.goTo(tmp[0]);
            this.selected_id = tmp[1];
            break;
          }
          this.show = "list";
          break;
      }
    },
    ifroute: function (rowData, route) {
      if (route.if) return route.if(rowData, this, false); //Harmadik paraméter mutatja h nem a részletekben van
      return true;
    },
    onHeaderContextmenu: function (e) {
      e.preventDefault();
      if (this.listableHeaders.length < 2) return;
      this.showHeaderContextmenu = true;
      var xpos;
      var ypos;
      if (e.pageX) {
        xpos = e.pageX;
      } else if (e.clientX) {
        xpos =
          e.clientX +
          (document.documentElement.scrollLeft
            ? document.documentElement.scrollLeft
            : document.body.scrollLeft);
      }

      if (e.pageY) {
        ypos = e.pageY;
      } else if (e.clientY) {
        ypos =
          e.clientY +
          (document.documentElement.scrollTop
            ? document.documentElement.scrollTop
            : document.body.scrollTop);
      }

      this.$nextTick(() => {
        var el = this.$refs.HeaderContextmenu;
        el.style.top = ypos - 10 + "px";
        el.style.left = xpos - 10 + "px";
      });

      document
        .getElementById("app")
        .addEventListener("click", () => (this.showHeaderContextmenu = false), {
          once: true,
        });

      return false;
    },
    ongridHeadContextMenuChange: function (val, name) {
      if (val == "I") {
        if (this.hiddenHeaders.includes(name)) {
          this.hiddenHeaders.splice(this.hiddenHeaders.indexOf(name), 1);
        }
      } else {
        if (this.listableHeaders.length - 1 == this.hiddenHeaders.length) {
          //ha csak 1 mradt,
          this.$refs["ContextHeaderField_" + name][0].setValue("I");
          return;
        }

        if (!this.hiddenHeaders.includes(name)) {
          this.hiddenHeaders.push(name);
        }
      }
    },
    onRowClass: function (rowData, index) {
      var out = [];
      if (
        this.useStatus &&
        !(rowData.status == "I" || rowData.status == "Aktív")
      )
        out.push("INACTIVE");
      if (this.setRowClass) {
        var out2 = this.setRowClass(rowData, index);
        if (isArray(out2)) {
          out2.forEach((element) => {
            out.push(element);
          });
        } else if (isString(out2)) {
          out2.split(" ").forEach((element) => {
            out.push(element);
          });
        }
      }
      return out;
    },
    download: function (rowData) {
      getStore(this.store).download(rowData.id);
    },
    canCreate: function () {
      if (this.createJog) {
        if (!getJog(this.createJog)) return false;
      }
      return (
        this.isCreate || this.hasSlot("create", "pureCreate", "formCreate")
      );
    },
    canUpdate: function (item) {
      if (this.updateJog) {
        if (!getJog(this.updateJog)) return false;
      }

      return this.canRowUpdate
        ? this.canRowUpdate(item)
        : this.isUpdate || this.hasSlot("update", "pureUpdate", "formUpdate");
    },
    canDetaile: function (item) {
      return this.canRowDetaile
        ? this.canRowDetaile(item)
        : this.hasSlot("details");
    },
  },
  components: {
    SearchPanel,
    FastFilterCmp,
    ImportCmp,
    Queque
  },

  directives: {
    tooltip: {
      mounted: function (el) {
        if (el.offsetWidth < el.scrollWidth) {
          el.setAttribute("title", el.textContent);
        } else {
          el.hasAttribute("title") && el.removeAttribute("title");
        }
      },
    },
  },
};
</script>

<style>
.GridSearchPanel.notCollapsed>.Body {
  border-left: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
}

.GridBody>table {
  min-width: 100%;
}

.GridRow.inactive>td {
  color: #ccc;
}

.GridRow>td {
  padding: 0;
}

.GridRow>td:last-child,
.headerRow>tr>th:last-child {
  width: 100%;
}

.gridCheckbox {
  padding: 0;
  margin: 0;
}

.GridCelContent {
  vertical-align: middle;
  display: inline-block;
}

.maxCellWidth.GridCelContent {
  max-width: 300px;
}

.GridCelContent {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.GridRow>td:last-child .GridCelContent,
.headerRow>tr>th:last-child .GridCelContent {
  max-width: initial;
}

.GridHeader {
  display: flex;
  flex-direction: row;
  gap: 10px;
}

.GridTools.toolbar {
  flex-wrap: nowrap;
}

.GridBodyParent {
  border: 1px solid #ddd;
  border-top: none;
  /* padding-top: 10px; */
}

.gridBodySpacer {
  height: 10px;
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
}

.GridBodyPanel {
  margin-right: 15px;
  margin-bottom: 15px;
  background-color: #fff;
  /* box-shadow: 0px 0px 4px 2px #ddd; */
}

.GridMask {
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
}

.GridBody {
  padding: 0 10px;
}

.GridBody>table {
  border-collapse: separate;
  border-spacing: 0;
}

.fastFilter {
  padding: 10px 10px 0 10px;
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
}

.GridBody>table>thead th {
  font-weight: initial;
  background-color: #515e6f;
  color: #fff;
  padding: 12px 12px 8px 12px;
}

.GridBody>table>thead th {
  position: -webkit-sticky;
  position: sticky;
  top: 0;
  z-index: 1;
}

.GridHeader {
  font-weight: bold;
}

th.sorted>.GridHeader {
  color: #ff8200;
}

.headerRow>tr>th {
  border-bottom: 4px solid #515e6f;
}

.headerRow>tr>th.sorted {
  border-bottom: 4px solid #ff8200;
}

.GridSmallDetail>td,
.GridRow>td {
  border-bottom: 1px solid #ddd;
}

tr.GridRow:last-child td {
  border-bottom: none;
}

.GridCelContent {
  padding: 8px;
}

.Grid .GridBody tr.GridRow:hover td,
.Grid .GridBody tr td.selected {
  background-color: #eee;
}

.GridTools .DetaileDownButton {
  background-color: transparent;
  color: var(--higlight-text-color);
  border: none;
}

.GridTools.toolbar {
  padding: 0 5px;
}

.GridTools.toolbar .icon {
  font-size: 15px;
}

.GridTools.toolbar .Button {
  min-width: 25px;
}

.GridBodyPanel>.Pager .pbtn>.icon {
  color: var(--title-color);
}

.GridBodyPanel>.Pager {
  background-color: var(--title-background-color);
  color: var(--title-color);
  height: 50px;
  padding: 0 10px;
}

.GridBodyPanel>.Pager>.pagingBody>.Button {
  color: #fff;
}

.Detail {
  background-color: #f1f1f1;
}

/*előre definiált sor színezések*/
.RED td {
  background-color: #ff797924;
}

.Grid .GridBody tr.GridRow.RED:hover td {
  background-color: #ff797973;
}

.GREEN td {
  background-color: #97e79724;
}

.Grid .GridBody tr.GridRow.GREEN:hover td {
  background-color: #97e79769;
}

.YELLOW td {
  background-color: #ffffbf;
}

.Grid .GridBody tr.GridRow.YELLOW:hover td {
  background-color: #f5f582;
}

.INACTIVE td {
  color: #999;
}

/* kisrészletek  */
.SmallDetailForm .formBody .fieldLabel,
.SmallDetailForm .formBody .FieldInner {
  /* border-bottom: 1px solid #ddd; */
  padding-top: 8px;
  padding-bottom: 8px;
  color: #333;
  display: inline-block;
}

.SmallDetailForm .formBody .fieldLabel {
  width: 160px;
}

.SmallDetailForm .formBody .FieldInner {
  width: 250px;
}

.SmallDetailForm .formBody .FieldInner {
  font-weight: bold;
}

.LockButton .icon {
  color: #cccc00;
}

.GridSpinner .icon {
  font-size: 70px;
  color: var(--higlight-text-color);
}

.gridHeadContextMenu {
  z-index: 999;
  position: absolute;
  padding: 15px;
  top: 0;
  left: 0;
}
</style>