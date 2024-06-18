<template>
  <div class="Detail vflex fit" v-if="refreshShow">
    <Panel
      title="Részletek"
      class="DetailPanel"
      :size="size + 'px'"
      border
      h
      collapsible
      ref="DetailPanel"
      @onCollapse="onCollapse"
    >
      <template #title>
        <div
          class="vflex toolbar"
          :style="isCollapsed ? null : (
            'width: ' +
            (size - 42) +
            'px;flex-wrap: nowrap;overflow: hidden;text-overflow:ellipsis;')
          "
        >
          <Button v-if="back" @click="doBack" icon="close" title="Bezárás" />
          <template v-if="rowData">
            <slot name="detailtoolbar" v-bind:rowData="rowData" />
            <Button
              title="Frissítés"
              v-if="detailsrefreshbtn"
              @click="fullRefresh"
              icon="pageRefresh"
            />
            <Button
              v-if="update"
              icon="edit"
              title="Módosítás"
              @click="onUpdate()"
            />
            <template v-if="uRoutes">
              <template v-for="(route, index) in uRoutes">
                <Button
                  v-if="ifroute(rowData, route)"
                  :key="index"
                  :icon="route.icon"
                  :quequeDisable="route.quequeDisable"
                  :title="route.title"
                  @click="
                    route.click
                      ? douclick(route, $event, _self, rowData)
                      : goTo(index)
                  "
                />
              </template>
            </template>
            <slot v-bind:rowData="rowData" name="detailrowBtn" />
            <Button
              :key="del"
              v-if="del"
              icon="delete"
              title="Törlés"
              @click="onDelete()"
            />
          </template>
          <div class="cflex">{{ title ? title : "Részletek" }}</div>
        </div>
      </template>
      <Form
        class="DetailForm"
        :fields="fields"
        :store="store"
        :record_id="record_id"
        load
        ref="form"
        :loaded="loaded"
        noFixWidth
        topToolbar
        :data="gridData"
      >
        <template #formTop>
          <slot v-if="rowData" :rowData="rowData" name="detailformSlot" />
        </template>
        <template #afterFields>
          <slot v-if="rowData" :rowData="rowData" name="detailunderFormSlot" />
        </template>
      </Form>
    </Panel>
    <slot v-bind:rowData="rowData" v-bind:record_id="record_id" />
  </div>
</template>

<script>
export default {
  name: "Detail",
  mounted: function () {
    var grid = this.up("Grid");
    //ha be volt csukva a panel akkor maradjon becsukva
    if (grid.isDetailCollapsed) {
      this.$refs.DetailPanel.isCollapsed = true;
    }
    this.doCollapse();
  },
  props: {
    size: {
      default: "350",
      type: String,
    },
    fields: Array,
    uRoutes: Object,
    store: String,
    record_id: [String, Number],
    detailsrefreshbtn: Boolean,
    title: String,
  },
  data: function () {
    var grid = this.up("Grid");
    return {
      isCollapsed: false,
      back: grid ? true : false,
      refreshShow: true,
      gridData: grid.$attrs.data
        ? grid.$attrs.data.filter((r) => r.id == this.record_id).shift()
        : null,
      rowData: grid.$attrs.data
        ? grid.$attrs.data.filter((r) => r.id == this.record_id).shift()
        : null,
      // loading: grid.$attrs.data ? false : true, //Duplán töltötte be a grid store-okat részletek ablakon belül
    };
  },
  computed: {
    hasMainPanel: function () {
      return this.hasSlot();
    },
    update: function () {
      var grid = this.up("Grid");
      if (this.rowData) {
        return grid.canRowUpdate
          ? grid.canRowUpdate(this.rowData)
          : grid.canUpdate(this.rowData);
      } else {
        return false;
      }
    },
    del: function () {
      var grid = this.up("Grid");
      if (this.rowData) {
        return grid.canRowDel ? grid.canRowDel(this.rowData) : grid.del;
      } else {
        return false;
      }
    },
  },
  methods: {
    refresh: function () {
      if(this.$refs.form)this.$refs.form.doload();
    },
    refreshAll: function () {
      this.refreshShow = false;
      this.$nextTick(() => (this.refreshShow = true));
    },
    loaded: function (s, rowData) {
      var me = this;
      if (!s) return this.doBack(); //Ha nincs pl jog akkor bezárjuk

      //ha entitás nincs kiválasztva vagy linkből nyitjuk meg a részletek ablakt, akkor aktív entitást is módosítani kell
      if (
        rowData.entity_id &&
        this.$root.entity &&
        this.$root.entity.active != rowData.entity_id
      ) {
        this.$root.changeEntity(rowData.entity_id, function () {
          //entitás értékét frissíteni kell
          me.$refs.form.reRender();
        });
      }

      this.up("Grid").rowData = rowData;
      this.rowData = rowData;
      // this.loading = true;
      // this.$nextTick((c) => (this.loading = false));
    },
    douclick: function (route, $event, _self, rowData) {
      route.click($event, _self, rowData, this.refresh);
    },
    doBack: function () {
      this.doUnCollapse();
      this.up("Grid").toList();
    },
    fullRefresh: function () {
      var grid = this.up("Grid");
      grid.toList();
      this.$nextTick(function () {
        grid.onDetails(this.rowData);
      });
    },
    doCollapse: function () {
      var parent = this.up("Detail");
      if (parent) {
        parent.$refs.DetailPanel.isCollapsed = true;
        parent.doCollapse();
      }
    },
    doChildCollapse() {
      var child = this.down("Detail");
      if (child) {
        var child2 = child.down("Detail");
        if (child2) {
          child.$refs.DetailPanel.isCollapsed = true;
          child.doChildCollapse();
        }
      }
    },
    doUnCollapse: function () {
      var parent = this.up("Detail");
      if (parent) {
        parent.$refs.DetailPanel.isCollapsed = false;
      }
    },
    onUpdate: function () {
      this.up("Grid").goTo("update", this.rowData, true);
    },
    onDelete: function () {
      this.up("Grid").onDelete(this.rowData, true);
    },
    goTo: function (routes) {
      this.up("Grid").goTo(routes, this.rowData, true);
    },
    ifroute: function (rowData, route) {
      if (route.if) return route.if(rowData, this, true); //Harmadik paraméter mutatja h részletek vagy nem
      return true;
    },
    onCollapse: function (val) {
      // this.up("Grid").isDetailCollapsed = val;
      // dd (val);
      this.isCollapsed = val;
      if (val == false) {
        this.doCollapse();
        this.doChildCollapse();
      }
    },
  },
};
</script>

<style>
/* .Grid .Detail {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 50;
} */

/* Details form */
.DetailForm .formBody .fieldBorder,
.DetailForm .formBody .fieldbody {
  background-color: transparent;
  border: none;
  margin: 0;
  padding: 0;
}

.DetailForm .formBody .fieldLabel,
.DetailForm .formBody .FieldInner {
  border-bottom: 1px solid #ddd;
  padding-top: 8px;
  padding-bottom: 8px;
  color: #333;
}

.DetailForm .formBody .FieldInner {
  font-weight: bold;
}
.DetailPanel {
  margin-right: 15px;
}

.DetailPanel > .Body {
  border-right: 1px solid #ddd;
  border-left: 1px solid #ddd;
}
</style>