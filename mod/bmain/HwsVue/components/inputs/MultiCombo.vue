<template>
  <div class="MultiCombo fit">
    <div class="multicomboselected" v-show="valueObj[Object.keys(valueObj)[0]]">
      <div
        v-for="(data, index) in valueObj"
        :key="'sv_' + data.value"
        class="multicomboselectedrow fieldbody vflex"
        :class="tmpRowClass(data, index)"
      >
        <input
          type="text"
          class="fit fieldBorder"
          :value="data.name"
          tabindex="-1"
          readonly
        />
        <div
          @click="delSelect(index)"
          class="fieldButton cflex pointer"
          tabindex="0"
          @keydown.space="delSelect(index)"
        >
          <div class="icon delete"></div>
        </div>
      </div>
    </div>
    <Combo
      ref="Combo"
      v-bind="$attrs"
      :manualSelect="manualSelect"
      :selected="valueObj"
      :required="required"
      :rowClass="rowClass"
      :disabled="disabled"
      comboIcon="103"
    />
  </div>
</template>

<script>
import Combo from "./Combo.vue";
export default {
  mixins: [fieldMixin],
  props: {
    rowClass: Function,
  },
  data: function () {
    return {
      parent_id: false,
      valueObj: [],
    };
  },
  components: {
    Combo,
  },
  watch: {
    value: function (val) {
      if (this.noValueChange) return;
      if (empty(val)) return this.refreshValues([]);
      if (this.parent_id && !this.first) return;
      this.setValues(val);

      //a szülő szól a gyerekeknek, hogy megváltozott az értéke
      var field = this.up("Field"),
        form = field.up("Form"),
        children;
      if (form) {
        children = field.up().findFields({ parent: field.name }, false);
        if (!empty(children)) {
          children.each(function (ch) {
            if (ch.$refs.input.parentChange) ch.$refs.input.parentChange(val);
          });
        }
      }
    },
    parent_id: function (val) {
      if (this.value || !this.first) this.setValues(this.value);
      this.first = true;
    },
  },
  methods: {
    setValues: function (val) {
      if (!val) return;
      var me = this,
        valueObj = [],
        values,
        values2 = [],
        combo = this.$refs.Combo;
      if (isArray(val)) {
        values = val;
      }
      if (isNumeric(val)) {
        values = [val];
      }

      if (isString(val)) {
        values = val
          .replace(/^\[/, "")
          .replace(/\]$/, "")
          .replace(/"/g, "")
          .replace(/'/g, "")
          .split(",");
      }

      //duplikált idk szűrése megszüntetése
      values = values.filter(function (item, index) {
        return values.indexOf(item) === index;
      });

      if (me.parent_id) {
        values2 = values;
      } else {
        for (var i in values) {
          var found = combo.getRecord(values[i]);
          if (found) {
            valueObj.push(found);
          } else {
            values2.push(values[i]);
          }
        }
      }

      var q = { value: values2.join(",") };

      if (me.parent_id) q.parent_id = me.parent_id;

      if (values2.length && combo.store) {
        var tmp = combo.store.split("|"),
          stname = tmp.shift();
        if (!empty(tmp)) {
          for (var i in tmp) {
            var tmp2 = tmp[i].split("=");
            q[tmp2[0]] = tmp2[1];
          }
        }

        if (this.$root.debug) {
          //formfill közben ne csináljon felesleges request-eket
          if (FormFill.run) {
            var rep = getStore(stname).$lastResponse;

            for (var i in values2) {
              var found = combo.getRecord(values2[i], rep ? rep.data : []);
              if (found) {
                valueObj.push(found);
              }
            }
            me.refreshValues(valueObj);
            return;
          }
        }

        // kell-e store betöltés, mert hiányzik a value-hoz a name;
        var tmpObjValues = this.valueObj.map((x) => x.value),
          needLoad =
            values2.filter((c) => !tmpObjValues.includes(c)).length != 0;

        if (needLoad) {
          combo.isLoading = true;
          getStore(stname).load(q, "noSortPage", "noTotal", function (s, d) {
            combo.isLoading = false;
            if (!s) return;
            for (var i in values2) {
              var found = combo.getRecord(values2[i], d.data);
              if (found) {
                valueObj.push(found);
              }
            }
            me.refreshValues(valueObj);
          });
        }
      } else {
        me.refreshValues(valueObj);
      }
    },
    refreshValues: function (valueObj) {
      this.noValueChange = true;
      this.valueObj = valueObj;
      var value = [];
      for (var i in this.valueObj) {
        value.push(this.valueObj[i].value);
      }
      this.value = value;
      this.$nextTick(function () {
        this.noValueChange = false;
      });
    },
    manualSelect: function (value, name) {
      if (value && name) {
        this.valueObj.push({
          value: value,
          name: name,
        });
        this.value = this.value || [];
        this.value.push(value);
      }
    },
    delSelect: function (index) {
      var val = this.valueObj[index].value;
      this.valueObj.splice(index, 1);
      this.value.splice(this.value.indexOf(val), 1);
      if (this.value.length == 0) this.value = null;
    },
    tmpRowClass: function (row, index) {
      var out;
      if (this.rowClass) out = this.rowClass(row, index);
      return out;
    },
    parentChange: function (parent_id) {
      this.$refs.Combo.parentChange(parent_id);
    },
  },
};
</script>