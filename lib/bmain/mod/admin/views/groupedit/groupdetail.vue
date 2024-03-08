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
    <Panel title="Modulok"></Panel>
  </Tab>
</template>
<script>
export default {
  props:{
    rowData: Object,
  },
  methods: {
    setRowClass: function (rowData) {
      if (rowData.user_has !==0) return "GREEN";
    },
    AddUser: function () {
      console.log('adduser');
    },
    DeleteUser: function () {
      console.log('deleteuser');
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