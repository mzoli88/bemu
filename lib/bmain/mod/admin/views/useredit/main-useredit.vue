<template>
  <Grid
    store="users"
    title="Felhasználók"
    create
    update
    :setRowClass="setRowClass"
  >
    <template v-slot:rowBtnEnd="rowData">
      <Button
        v-if="rowData.id != getUserData().id"
        icon="234"
        title="Active"
        @click="activateUser(rowData)"
      />
    </template>
    <template v-slot:details="d">
      <Perms v-bind="d" />
    </template>
  </Grid>
</template>

<script>
export default {
  components: {
    Perms: cLoad("useredit.perms"),
  },
  methods: {
    setRowClass: function (rowData) {
      if (rowData.id == getUserData().id) return "GREEN";
    },
    activateUser: function (rowData) {
      maskOn ();
      getStore("activate").load(rowData.id,function(s){
        if(s)location.reload();
      });
    },
  },
};
</script>