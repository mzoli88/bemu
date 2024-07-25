<template>
  <Grid checkbox :checkboxIf="checkboxIf" store="email" title="E-mail" sort="id" desc :uRoutes="{
    add: {
      title: 'Újraküldés',
      icon: 'f3e5',
      click: toogle,
      if: canResend,
    },
  }"   :cRoutes="{
    add: {
      title: 'Lista küldés',
      icon: 'f14d',
      click: toogle2,
      if: canResendChecked
    },
  }"
  :setRowClass="setRowClass"
  entity
 />
</template>

<script>
export default {
  methods: {
    toogle: function (e, cmp, r) {
      if (!getJog("cnadmin")) return;
      ask('Biztosan újra szeretné küldeni az e-mailt?', x => {
        getStore("email").update(r.id);
        getStore("email").load();
      })
    },

    checkboxIf: function(rowData){
      return rowData.has_attachment && rowData.state == 2;
    },

    setRowClass: function(rowData){
      if (rowData.state == 2)return 'RED';
      if (rowData.state == 1)return 'YELLOW';
    },

    toogle2: function (e, grid, data) {
      var grid = this.down("Grid");
      var allId = grid.getCheckboxSelected();
      if (allId.length == 0) {
        msg("Kérem, válasszon ki legalább egy újra küldentő e-mailt!", "error");
        return;
      }
      msg(
        "Biztosan újra szeretné küldeni a kijelölt e-maileket?",
        "i/n",
        function (s) {
          if (!s) return;
          getStore("email").create(
            { 
              ids: allId,
            },
            function (s, data, code) {
              if (!s) {
                var title = "Sikertelen újraküldés!";
                var message = null;
                if (data.title) {
                  title = data.title;
                }
                if (data.message) {
                  message = data.message;
                }
                msg(title, "error", null, message);
              } 

              grid.chboxSelectedNumber = 0;
              grid.load();
              
            }
          );
        },
        "Kijelölt e-mailek száma: " + allId.length + " db"
      );
    },

    canResend: function (rowData) {
      return rowData.state != 1 && rowData.has_attachment && hasPerm("cnadmin");
    },

    canResendChecked: function (rowData) {
      return hasPerm("cnadmin");
    }

  },
};
</script>

