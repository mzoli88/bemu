<template>
  <div class="radioGroup inputgroup">
    <div class="fieldbody" :class="{ vflex: igennem, gap: igennem }">
      <div class="vflex" v-for="(box, index) in dinamicBox" :key="index">
        <span class="noselect blankch">&nbsp;</span>
        <div
          class="rhbox fieldBorder pointer cflex"
          :class="{ required }"
          :tabindex="disabled ? null : 0"
          @keypress.space="onChange(box.value)"
          @click="onChange(box.value)"
        >
          <span class="icon" :class="{ checked: box.value == value }"> </span>
        </div>
        <div
          class="cflex noselect radiotext pointer"
          @click="onChange(box.value)"
        >
          {{ box.name }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  props: {
    boxes: Array,
    store: String,
    noEdit: Boolean,
    igennem: Boolean,
  },
  data: function () {
    return {
      dinamicBox: this.boxes || [],
    };
  },
  created: function () {
    if (this.store) {
      var me = this,
        query = {};
      query["sort>"] = "name";

      var tmp = this.store.split("|"),
        stname = tmp.shift();

      if (!empty(tmp)) {
        for (var i in tmp) {
          var tmp2 = tmp[i].split("=");
          query[tmp2[0]] = tmp2[1];
        }
      }

      getStore(stname).load(query, function (s, data) {
        if (s) {
          me.dinamicBox = data.data;
          // me.setFirstValue();
        }
      });
    } else {
      // this.setFirstValue();
    }
  },
  watch: {
    value: function (val) {
      var me = this;
      var find = me.dinamicBox.filter(function (box) {
        return val == box.name && box.value != val;
      });
      if (find.length == 1) {
        this.value = find[0].value;
      }
    },
  },
  methods: {
    // setFirstValue: function () {
    //   if (!this.value && this.dinamicBox.length)
    //     this.value = this.dinamicBox[0].value;
    // },
    onChange: function (value) {
      if (this.disabled) return false;
      if (this.noEdit) return;
      this.value = value;
    },
  },
};
</script>

<style>
.rhbox {
  position: relative;
  padding: 0 !important;
  height: 18px;
  width: 18px;
  border-radius: 100px !important;
  line-height: initial;
}
.radiotext {
  margin-left: 5px;
}
</style>