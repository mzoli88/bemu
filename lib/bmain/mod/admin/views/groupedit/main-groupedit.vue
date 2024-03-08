<template>
 <Grid store="groups" title="Szerepkör kezelés">
  <template v-slot:details="d">
      <Groupdetail v-bind="d"/>
    </template>

 </Grid>
 
</template>

<script>
export default {
  components: {
    Groupdetail: cLoad("groupedit.groupdetail"),

  },
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("group_admin") && !isSysAdmin()) return;

      getStore("users").update(r.id);
      getStore("users").load();
    }
  },
};
</script>

<style scoped>
.add.Button {
  background-color: #aaa;
}
</style>