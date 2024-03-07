<template>
  <div class="ColDiagram vflex fit scroll">
    <div
      class="ColDiagramMain hflex pointer"
      v-for="(d, i) in usedData"
      :key="i + '_k_' + d.value"
      :title="d.title || d.name"
    >
      <span class="hiddenText">{{ d.value }}</span>
      <div class="ColDiagramTop">
        <div class="D_col" v-height="d.value">
          <span class="hiddenText">{{ d.value }}</span>
          <div class="D_floatVAlue">
            {{ d.value }}
          </div>
        </div>
      </div>
      <div class="ColDiagramBottom">
        <div class="D_colNames rotate">{{ d.name }}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    data: Array,
    store: String,
    load: Boolean,
  },

  data: function () {
    return {
      max: 0,
      usedData: this.data,
    };
  },

  activated: function () {
    if (this.load) this.doLoad();
  },

  created: function () {
    this.max = this.getMaxVal();
  },

  mounted: function () {
    if (this.store) getStore(this.store).onLoad(this.onLoad, this);
    if (this.load) this.doLoad();
  },

  directives: {
    height: {
      mounted: function (el, binding) {
        if (!binding.value || binding.value == 0) {
          el.style.height = "0%";
          return;
        }
        el.style.height =
          Math.round((binding.value / binding.instance.max) * 100) + "%";
      },
    },
  },

  watch: {
    usedData: function (val) {
      this.max = this.getMaxVal();
    },
  },

  methods: {
    doLoad: function () {
      if (this.store) getStore(this.store).load();
    },
    tabPanelChange: function () {
      if (this.load) this.doLoad();
    },
    getMaxVal: function () {
      if (empty(this.usedData)) return null;
      return Math.max.apply(
        Math,
        this.usedData.map(function (o) {
          return o.value;
        })
      );
    },
    getHeight: function (val) {
      var ob = 300 / this.max;
      return val * ob + "px";
    },
    onLoad: function (s, d) {
      if (!s) return;
      if (isArray(d)) {
        this.usedData = d;
      } else {
        if (d.data) this.usedData = d.data;
      }
    },
  },
};
</script>

<style scoped>
.ColDiagramMain {
  position: relative;
  margin: 5px;
  min-width: 50px;
}

.ColDiagramTop {
  position: relative;
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
}

.ColDiagramBottom {
  flex-basis: 100px;
  flex-grow: 0;
  flex-shrink: 0;
  position: relative;
}

.D_col {
  min-width: 50px;
  border: 1px solid #aaa;
  position: absolute;
  bottom: 0;
  background-color: greenyellow;
  text-align: center;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
}

.D_colNames {
  text-align: center;
  position: absolute;
  top: 5px;
  left: 22px;
  transform: rotate(20deg);
  transform-origin: top left;
  white-space: nowrap;
  /* max-width: 200px; */
  /* overflow: hidden; */
  text-overflow: ellipsis;
  text-align: left;
}

.D_floatVAlue {
  text-align: center;
  position: absolute;
  min-width: 50px;
  top: -20px;
}

.hiddenText {
  opacity: 0;
}
</style>