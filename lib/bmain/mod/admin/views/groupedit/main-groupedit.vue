<template>
  <Grid store="groups" title="Szerepkör kezelés">
    <template #details>
      <Tab>
        <Panel title="Felhasználók">
          <Grid
            store="users"
            title="Felhasználók"
            parent="group_id"
            :uRoutes="{
              add: {
                title: 'Hozzáadás',
                icon: 'f0c8',
                if: (r) => !r.in_group,
                click: toogle,
              },
              del: {
                title: 'Eltávolítás',
                icon: 'f14a',
                if: (r) => r.in_group,
                click: toogle,
              },
            }"
          />
        </Panel>
        <Panel title="Jogosultságok"> <Perms /> </Panel>
      </Tab>
    </template>
  </Grid>
</template>
<script>
export default {
  components: {
    Perms: cLoad("groupedit.perms"),
  },
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("group_admin") && !isSysAdmin()) return;

      getStore("users").update(r.id);
      getStore("users").load();
    },
  },
};
</script>

<style scoped>
.add.Button {
  background-color: #aaa;
}
</style>