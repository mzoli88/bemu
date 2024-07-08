<template>
  <div class="checkboxgroup inputgroup" @dblclick="ondblclick">
    <div class="vflex">
      <div>
        <div v-if="!disabled && search" class="hflex">
          <div class="fieldbody fit vflex">
            <input type="text" class="Textfield fit fieldBorder" autocomplete="off" placeholder="Keresés"
              v-model="searchText" />
          </div>
          <Field v-for="(field, i) in extrasearch" :key="'extra' + i" v-bind="field" ref="fields"
            @change="onfieldChange" nofindFields />
        </div>
        <div class="fieldbody vflex fit" v-for="(box, index) in dinamicBox" :key="index">
          <span class="noselect blankch">&nbsp;</span>
          <div class="chbox fieldBorder pointer cflex" :class="{ required }" :tabindex="disabled ? null : 0"
            @keypress.space="onChange(index)" @click="onChange(index)">
            <span class="icon" :class="{ checked: isChecked(box.value) }"></span>
          </div>
          <div class="cflex noselect chboxtext pointer wrap" @click="onChange(index)" v-html="box.name"></div>
        </div>
        <Pager v-if="!disabled && search && store" :store="store" :load="load" />
      </div>
      <div class="padding chboxSelectedPanel" v-if="showSelected">
        <div class="chboxSelectedPanelTitle" style="margin-bottom: 10px">
          Kiválasztott: {{ value ? value.length : 0 }} db.
        </div>
        <div>
          <div class="fieldbody vflex fit" v-for="(selected, index) in sortSelectedRecordsByName" :key="'sel_' + index">
            <div class="chbox fieldBorder pointer cflex" :tabindex="disabled ? null : 0"
              @keypress.space="delSelected(selected.value)" @click="delSelected(selected.value)">
              <span class="icon cflex">&#xf00d;</span>
            </div>
            <div class="cflex noselect chboxtext pointer wrap">
              {{ selected.name }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pager from "../Pager.vue";
export default {
  mixins: [fieldMixin],
  emits: ["recordChange"],
  props: {
    boxes: Array,
    store: String,
    search: Boolean,
    showSelected: Boolean,
    noSelectedSort: Boolean,
    noEdit: Boolean,
    extrasearch: Array,
    query: Object,
  },
  mounted: function () {
    this.load();
  },
  data: function () {
    return {
      searchText: null,
      dinamicBox: this.boxes || [],
      selectedRecords: [],
    };
  },
  computed: {
    sortSelectedRecordsByName: function () {
      if (this.noSelectedSort) return this.selectedRecords;
      return this.selectedRecords.sort((firstEl, secondEl) =>
        firstEl.name < secondEl.name ? -1 : 1
      );
    },
  },
  watch: {
    value: {
      deep: true,
      handler: function (val) {
        if (val == null) {
          this.selectedRecords = [];
          return this.$emit("recordChange", this.selectedRecords);
        }
        if (isArray(val)) {
          var noDuplicates = val.filter(function (item, index) {
            return val.indexOf(item) === index;
          });
          if (noDuplicates.length != val.length) {
            this.value = noDuplicates;
            return;
          }
          this.updateSelectedRecords();
          this.$emit("recordChange", this.selectedRecords);
          return;
        }
        if (isNumber(val)) {
          this.value = [val];
          return;
        }
        if (isString(val)) {
          this.value = val
            .replace(/^\[/, "")
            .replace(/\]$/, "")
            .replace(/"/g, "")
            .replace(/'/g, "")
            .split(",");
          return;
        }
        this.value = null;
        this.selectedRecords = [];
      },
    },
    searchText: function (val) {
      clearTimeout(this.delayTimer);
      if (val == this.displayValue) return;
      if (!this.search || !isString(val)) return;

      var me = this;
      me.delayTimer = setTimeout(function () {
        me.load();
      }, 1000);
    },
  },
  methods: {
    load: function () {
      if (this.store) {
        var me = this,
          query = {};
        query["sort>"] = "name";
        if (this.query) query = { ...query, ...this.query };
        var tmp = this.store.split("|"),
          stname = tmp.shift();
        if (!empty(tmp)) {
          for (var i in tmp) {
            var tmp2 = tmp[i].split("=");
            query[tmp2[0]] = tmp2[1];
          }
        }

        if (this.search) {
          if (!empty(this.searchText)) query.name = this.searchText;
          if (!empty(this.$refs.fields)) {
            for (var i in this.$refs.fields) {
              var elem = this.$refs.fields[i];
              if (elem.name && elem.getValue()) {
                query[elem.name] = elem.getValue();
              }
            }
          }
        }
        getStore(stname, "noSortPage").load(query, function (s, data) {
          if (s) {
            me.dinamicBox = data.data;
          }
        });
      }
    },
    isChecked: function (value) {
      if (empty(this.value)) return false;
      //include nem jó mert string-et rosszul hasonlítja össze a számmal
      return this.value.filter((x) => x == value).length != 0;
    },
    updateSelectedRecords: function () {
      var me = this,
        oldselectedRecords = this.selectedRecords,
        notFoundRecordIds = [];

      this.selectedRecords = [];
      me.value.forEach(function (id) {
        var foundRecord = oldselectedRecords.find((row) => row.value == id);

        if (empty(foundRecord)) {
          var foundRecord = me.dinamicBox.find((row) => row.value == id);
          if (empty(foundRecord)) {
            notFoundRecordIds.push(id);
          } else {
            me.selectedRecords.push(foundRecord);
          }
        } else {
          me.selectedRecords.push(foundRecord);
        }
      });

      //Get records from store
      if (!empty(notFoundRecordIds) && !empty(this.store)) {

        //ha a value-ban szöveges elem van, akkor azokat csak json-el tudja keresni és nem vesszővel elválasztva
        var hasText = notFoundRecordIds.filter((id) => !isNumeric(id));

        var q = { value: hasText.length ? notFoundRecordIds : notFoundRecordIds.join(",") };
        var tmp = this.store.split("|"),
          stname = tmp.shift();

        if (!empty(tmp)) {
          for (var i in tmp) {
            var tmp2 = tmp[i].split("=");
            q[tmp2[0]] = tmp2[1];
          }
        }

        getStore(stname).load(q, "noSortPage", "noTotal", function (s, d) {
          if (!s) return;

          for (var i in notFoundRecordIds) {
            var id = notFoundRecordIds[i];
            var foundRecord = d.data.find((row) => row.value == id);
            if (!empty(foundRecord)) {
              me.selectedRecords.push(foundRecord);
            } else {
              me.delSelected(id);
            }
          }
        });
      }
    },
    onChange: function (i) {
      if (this.disabled) return false;

      if (this.isChecked(this.dinamicBox[i].value)) {
        this.delSelected(this.dinamicBox[i].value);
      } else {
        this.addSelected(this.dinamicBox[i].value);
      }
    },
    onfieldChange: function () {
      // dd(val, name.name);
      this.load();
    },
    addSelected: function (id) {
      if (this.disabled) return;
      if (this.noEdit) return;
      if (!isArray(this.value)) this.value = [];
      this.value.push(id);

      var foundRecord = this.dinamicBox.find((row) => row.value == id);
      if (foundRecord) {
        this.selectedRecords.push(foundRecord);
      } else {
        this.updateSelectedRecords();
      }
    },
    delSelected: function (id) {
      if (this.disabled) return;
      if (this.noEdit) return;
      this.selectedRecords = this.selectedRecords.filter(
        (row) => row.value != id
      );
      this.value = this.value.filter((val) => val != id);
      if (empty(this.value)) this.value = null;
    },
    ondblclick: function (e) {
      if (empty(this.value)) {
        var tmp = [];
        this.dinamicBox.forEach(function (r) {
          tmp.push(r.value);
        });
        this.value = tmp;
      } else {
        this.value = null;
      }
    }
  },
  components: {
    Pager,
  },
};
</script>