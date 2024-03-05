<template>
  <Tab>
    <Panel title="Téma"> <Smink /> </Panel>
    <Panel title="Menüpontok"> <Menuset /> </Panel>
    <Panel title="Bejelentkezési képernyő"> <Login /> </Panel>
    <Panel title="Jelszó kezelés / beléptetés"> <Security /> </Panel>
    <Panel title="Karbantartás" v-if="isSysAdmin()"> <Maintenance /> </Panel>
    <Panel title="Naplózás" v-if="isSysAdmin()"> <Naplo /> </Panel>
  </Tab>
</template>

<script>
export default {
  components: {
    Smink: cLoad("smink"),
    Menuset: cLoad("menuset"),
    Login: cLoad("login"),
    Security: cLoad("security"),
    Maintenance: cLoad("maintenance"),
    Naplo: cLoad("naplo"),
  },
};
</script>