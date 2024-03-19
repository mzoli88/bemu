<template>
  <Tab>
    <Panel title="Felhasználók">
      <Grid parent="group_id" store="users" :setRowClass="setRowClass" :uRoutes="{
        AddUser: {
          title: 'gomb1',
          if: (rowData) => rowData.user_has == 0,
          icon: '055',
          click: AddUser,
        },
        DeleteUser: {
          title: 'gomb2',
          if: (rowData) => rowData.user_has == 1,
          icon: 'Törlés',
          click: DeleteUser,

        }
      }">
      </Grid>
    </Panel>
    <Panel title="Modulok">
      <Grid parent="group_id" store="modules" :setRowClass="setRowClass" :uRoutes="{
        AddMod: {
          title: 'gomb1',
          if: (rowData) => rowData.user_has == 0,
          icon: '055',
          click: AddMod,
        },
        DeleteMod: {
          title: 'gomb2',
          if: (rowData) => rowData.user_has == 1,
          icon: 'Törlés',
          click: DeleteMod,
        }
      }" :cols="{
        id: true,
        name: {
          title: 'Modul'
        },
      }">
      </Grid>
    </Panel>
  </Tab>
</template>
<script>
export default {
  props: {
    record_id: [Number, String],
    rowData: Object,

  },
  data: function () {
    getStore("users").fixQuery("group_id", this.record_id)
  },
  methods: {
    setRowClass: function (rowData) {
      if (rowData.user_has !== 0) return "GREEN";
    },
    AddUser: function (e, b, rowData) {
      getStore("users").create(rowData).load();
    },
    DeleteUser: function (e, b, rowData) {
      getStore("users").delete(rowData.id).load();
    },
    AddMod: function (e, b, rowData) {
      getStore("modules").create(rowData).load();
    },
    DeleteMod: function (e, b, rowData) {
      getStore("modules").delete(rowData).load();
    },

  }
};
</script>
<style>
.pictoBtn.DeleteUser {
  background-color: transparent !important;
  color: red !important;
}

.pictoBtn.AddUser {
  background-color: transparent !important;
  color: green !important;
}
</style>