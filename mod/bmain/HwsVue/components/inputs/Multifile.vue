<template>
  <div class="Multifile fit hflex inputgroup">
    <div
      v-for="(d, i) in fields"
      :key="i"
      class="vflex MultiFileBody fieldbody"
    >
      <input
        type="text"
        class="fit fieldBorder"
        :value="d"
        tabindex="-1"
        readonly
      />
      <slot name="rowBtnSlot" :index="i" :d="values[i]"/>
      <div
        class="fieldButton cflex pointer"
        @click="downloadFile(values[i].link)"
        tabindex="0"
        v-if="values[i].link"
        @keydown.space="downloadFile(values[i].link)"
      >
        <span class="icon download"></span>
      </div>
      <div
        class="fieldButton cflex pointer"
        @click="delFile(i)"
        tabindex="0"
        @keydown.space="delFile(i)"
        v-if="!disabled && !noEdit"
      >
        <span class="icon delete"></span>
      </div>
    </div>
    <Filefield
      class="fit"
      @change="onChange"
      :required="required"
      ref="fileinput"
      :accept="$attrs.accept"
      :maxByte="$attrs.maxByte"
      v-if="!disabled && !noEdit && (!max || fields.length <= (max - 1))"
      multiple
    />
  </div>
</template>

<script>
import Filefield from "./File.vue";
export default {
  mixins: [fieldMixin],
  props:{
    max: Number,
    noEdit: Boolean
  },
  data: function () {
    //value-kat nem reaktívként tároljuk, mert a file content nagy
    this.values = [];
    return {
      fields: [],
    };
  },
  watch: {
    value: function (val) {
      if (empty(val)) {
        this.values = null;
        this.fields = [];
        if(this.$refs.fileinput)this.$refs.fileinput.reset();
      } else {
        this.values = val;
        this.fields = [];
        for (var i in val) {
          this.fields.push(val[i].name);
        }
      }
    },
  },
  methods: {
    addData: function (val) {
      this.onChange(val);
    },
    onChange: function (val) {
      if (val) {
        this.values = this.values || [];
        this.fields.push(val.name);
        this.values.push(val);
        this.value = this.values;
      }
      // this.$refs.fileinput.value = null;
      this.$refs.fileinput.reset();
    },
    delFile: function (i) {
      if(this.disabled)return;
      if(this.noEdit)return;
      delete this.fields.splice(i, 1);
      this.values.splice(i, 1);
      if (this.values.length == 0) this.values = null;
      this.value = this.values;
    },
    downloadFile: function (link) {
      if(this.disabled)return;
      window.open(link);
    },
  },
  components: {
    Filefield,
  },
};
</script>