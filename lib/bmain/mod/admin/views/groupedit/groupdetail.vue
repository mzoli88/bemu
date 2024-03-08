<template>
  <Tab>
    <Panel title="Felhasználók">
      <Grid store="users" :uRoutes="{
        AddUser: {
          title: 'gomb1',
          icon: 'f076',
          click: AddUser,
        },
        DeleteUser: {
          title: 'gomb2',
          icon: 'f076',
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
  methods: {
    AddUser: function () {
      console.log('adduser');
    },
    DeleteUser: function () {
      console.log('deleteuser');
    }
  }
};
</script>
