<template>
  <Grid
    title="Jogok"
    store="perms"
    :setRowClass="setRowClass"
    :cRoutes="{
      giveAll: {
        title: 'Összes jog hozzáadása',
        icon: '055',
        click: giveAll,
      },
      delAll: {
        title: 'Összes jog elvétele',
        icon: '068',
        click: delAll,
      },
    }"
    :uRoutes="{
      giveOne: {
        title: 'Hozzáadás',
        if: (rowData) => rowData.user_has == 0,
        icon: '055',
        click: giveOne,
      },
      delOne: {
        title: 'Törlés',
        if: (rowData) => rowData.user_has == 1,
        click: delOne,
      },
    }"
  >
  </Grid>
</template>


<script>
export default {
  props: {
    record_id: [Number, String],
    rowData: Object,
  },
  data: function () {
    getStore("perms").fixQuery("user_id", this.record_id);
    return {};
  },
  methods: {
    setRowClass: function (rowData) {
      if (rowData.user_has !== 0) return "info";
    },
    giveOne: function (e,b,rowData) {
      getStore("perms").create(rowData).load();
    },
    delOne: function (e,b,rowData) {
      getStore("perms").delete(rowData.csoport_id).load();
    },
    giveAll: function () {
      getStore("perms").create().load();
    },
    delAll: function () {
      getStore("perms").delete().load();
    },
  },
};
</script>

<style>
.pictoBtn.delOne {
  background-color: transparent !important;
  color: red !important;
}
.pictoBtn.giveOne {
  background-color: transparent !important;
  color: green !important;
}
</style>