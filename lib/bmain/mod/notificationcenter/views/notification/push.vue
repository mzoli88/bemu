<template>
  <Grid store="push" title="Push" sort="id" desc

  :uRoutes="{
    add: {
      title: 'Újraküldés',
      icon: 'f3e5',
      if: canResend,
      click: toogle,
    },
  }" 
  entity
  :setRowClass="setRowClass"
  />
</template>

<script>
export default {
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("cnadmin")) return;
      ask('Biztosan újra szeretné küldeni a push üzenetet?',x=>{
        getStore("push").update(r.id);
        getStore("push").load();
      })

    },

    setRowClass: function(rowData){
      if (rowData.state == 2)return 'RED';
      if (rowData.state == 1)return 'YELLOW';
    },

    canResend: function (rowData) {
      return rowData.state != 1 && hasPerm("cnadmin");
    },

    canResendChecked: function (rowData) {
      return hasPerm("cnadmin");
    }
  }
};
</script>