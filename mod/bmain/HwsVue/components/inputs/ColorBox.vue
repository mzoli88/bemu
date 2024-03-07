<template>
  <table class="colorTable dropdown" @click="onClick" cellspacing="0">
    <tbody>
      <tr>
        <td class="colorTableCell">
          <div data-color="#000000" style="background-color: #000000"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#993300" style="background-color: #993300"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#333300" style="background-color: #333300"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#003300" style="background-color: #003300"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#003366" style="background-color: #003366"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#000080" style="background-color: #000080"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#333399" style="background-color: #333399"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#444444" style="background-color: #444444"></div>
        </td>
      </tr>
      <tr>
        <td class="colorTableCell">
          <div data-color="#800000" style="background-color: #800000"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FF6600" style="background-color: #ff6600"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#808000" style="background-color: #808000"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#008000" style="background-color: #008000"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#008080" style="background-color: #008080"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#0000FF" style="background-color: #0000ff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#666699" style="background-color: #666699"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#888888" style="background-color: #888888"></div>
        </td>
      </tr>
      <tr>
        <td class="colorTableCell">
          <div data-color="#FF0000" style="background-color: #ff0000"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FF9900" style="background-color: #ff9900"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#99CC00" style="background-color: #99cc00"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#339966" style="background-color: #339966"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#33CCCC" style="background-color: #33cccc"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#3366FF" style="background-color: #3366ff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#800080" style="background-color: #800080"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#bbbbbb" style="background-color: #bbbbbb"></div>
        </td>
      </tr>
      <tr>
        <td class="colorTableCell">
          <div data-color="#FF00FF" style="background-color: #ff00ff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FFCC00" style="background-color: #ffcc00"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FFFF00" style="background-color: #ffff00"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#00FF00" style="background-color: #00ff00"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#00FFFF" style="background-color: #00ffff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#00CCFF" style="background-color: #00ccff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#993366" style="background-color: #993366"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#dddddd" style="background-color: #dddddd"></div>
        </td>
      </tr>
      <tr>
        <td class="colorTableCell">
          <div data-color="#FF99CC" style="background-color: #ff99cc"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FFCC99" style="background-color: #ffcc99"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#FFFF99" style="background-color: #ffff99"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#CCFFCC" style="background-color: #ccffcc"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#CCFFFF" style="background-color: #ccffff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#99CCFF" style="background-color: #99ccff"></div>
        </td>
        <td class="colorTableCell">
          <div data-color="#CC99FF" style="background-color: #cc99ff"></div>
        </td>
        <td class="colorTableCell mce-colorbtn-trans">
          <div data-color="#FFFFFF" style="background-color: #ffffff"></div>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  emits: ["select"],
  methods: {
    onClick: function (e) {
      var color = e.target.getAttribute("data-color");
      if (!empty(color)) this.$emit("select", color);
    },
  },
};
</script>


<style>
.colorTableCell div {
  border: 1px solid #d6d6d6;
  width: 15px;
  height: 15px;
  margin: 0;
  cursor: pointer;
}
.colorTableCell div:hover {
  border: 1px solid #aaa;
}
</style>