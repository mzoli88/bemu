<template>
      <Grid
        title="Felhasználó kezelés"
        :setRowClass="setRowClass"
        store="users"
        :uRoutes="{
          userUpdate: {
            title: 'Módosítás',
            icon: 'edit',
            if: canUpdate
          },
          user_lift_suspension: {
            title: 'Felhasználó felfüggesztés megszüntetése',
            icon: 'f3c1',
            if: canUnclock,
            click: onUnlock,
          },
          user_lift_lock: {
            title: 'Felhasználó zárolás megszüntetése',
            icon: 'f3c1',
            if: canUnclock2,
            click: onUnlock2,
          },
          user_lift_szunet: {
            title: 'Felhasználó szüneteltetés feloldása',
            icon: 'f3c1',
            if: canUnclock3,
            click: onUnlock3,
          },
          user_add_szunet: {
            title: 'Felhasználó szüneteltetése',
            icon: 'f28d',
            if: canUnclock4,
            click: onUnlock4,
          },
          resendEmail: {
            title: 'Aktiváló e-mail újraküldése',
            icon: 'f0e0',
            if: (r) => hasPerm('SysAdmin', 'users_admin') && r.state_id == 1,
            click: resendEmail,
          },
        }"
      >
        <template v-slot:userUpdate="props">
          <UserUpdate v-bind="props" />
        </template>

        <template v-slot:details="props">
          <Tab v-if="props && props.rowData && (props.rowData.state_id == 1 || props.rowData.state_id == 2)">
            <Panel title="Szerepkörök" >
              <Grid
                store="groups"
                parent="user_id"
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
    Perms: cLoad("useredit.perms"),
    UserUpdate: cLoad("useredit.userUpdate")
  },
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("group_admin") && !isSysAdmin()) return;
      getStore("groups").update(r.id);
      getStore("groups").load();
    },
    resendEmail: function (e, cmp, r) {
      msg("Biztosan újra küldi az aktiváló e-mailt?", "i/n", (s) => {
        if (s) getStore("resendemail").update(r.id);
      });
    },
    onUnlock: function (e, cmp, r) {
      msg("Biztosan megszünteti a felfüggesztést?", "i/n", (s) => {
        if (s)
          getStore("unlock").update(r.id, (x) => {
            getStore("users").load();
          });
      });
    },
    onUnlock2: function (e, cmp, r) {
      msg("Biztosan megszünteti a zárolást?", "i/n", (s) => {
        if (s)
          getStore("unlock").update(r.id, (x) => {
            getStore("users").load();
          });
      });
    },
    onUnlock3: function (e, cmp, r) {
      msg("Biztosan megszünteti a szüneteltetést?", "i/n", (s) => {
        if (s)
          getStore("unlock").update(r.id, (x) => {
            getStore("users").load();
          });
      });
    },
    onUnlock4: function (e, cmp, r) {
      msg("Biztosan szünetelteteti a felhasználót?", "i/n", (s) => {
        if (s)
          getStore("pause").update(r.id, (x) => {
            getStore("users").load();
          });
      });
    },
    canUnclock: function (r) {
      return (
        hasPerm("users_admin", "SysAdmin", "user_lift_suspension") &&
        r.state_id == 7
      );
    },
    canUnclock2: function (r) {
      return (
        hasPerm("users_admin", "SysAdmin", "user_lift_lock") &&
        r.state_id == 6
      );
    },
    canUnclock3: function (r) {
      return (
        hasPerm("users_admin", "SysAdmin", "user_szunet") &&
        r.state_id == 5
      );
    },
    canUnclock4: function (r) {
      return (
        hasPerm("users_admin", "SysAdmin", "user_szunet") &&
        r.state_id == 2
      );
    },
    setRowClass: function (r) {
      if (r.state_id == 7 || r.state_id == 6 || r.state_id == 5) return 'YELLOW'; 
      if (r.state_id == 3 || r.state_id == 4) return 'INACTIVE';
    },
    canUpdate: function (rowData) {
      return rowData.state_id != 3 && rowData.state_id != 4 && hasPerm("users_admin", "SysAdmin");
    }
  },
};
</script>

<style scoped>
.add.Button {
  background-color: #aaa;
}
</style>