<template>
  <Dropdown
    class="datepicker noselect"
    tabindex="0"
    @blur="obBlur"
    @keydown="onKeypress"
    noDfocus
  >
    <table>
      <tbody>
        <tr>
          <td>
            <div
              class="pbtn pointer cflex"
              @click="yearback"
              v-html="'<<'"
              :class="isSelected(-1, 0)"
            ></div>
          </td>
          <td>
            <div
              class="pbtn pointer cflex"
              @click="monthback"
              v-html="'<'"
              :class="isSelected(-1, 1)"
            ></div>
          </td>
          <td colspan="3">{{ year }} {{ getMonth(month) }}</td>
          <td>
            <div
              class="pbtn pointer cflex"
              @click="monthnext"
              v-html="'>'"
              :class="isSelected(-1, 5)"
            ></div>
          </td>
          <td>
            <div
              class="pbtn pointer cflex"
              @click="yearnext"
              v-html="'>>'"
              :class="isSelected(-1, 6)"
            ></div>
          </td>
        </tr>
        <tr class="daysRow">
          <td>Hé</td>
          <td>Ke</td>
          <td>Sze</td>
          <td>Cs</td>
          <td>Pé</td>
          <td>Szo</td>
          <td>Va</td>
        </tr>
        <tr v-for="(week, n) in dates" :key="'w_' + n">
          <td v-for="(day, k) in week" :key="'d_' + k">
            <div
              class="daybutton pbtn cflex"
              :class="[
                day.cls,
                isSelected(n, k),
                day.disabled ? null : 'pointer',
              ]"
              @click="day.disabled ? null : onSelect(day.value)"
            >
              {{ day.text }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </Dropdown>
</template>

<script>
export default {
  props: {
    select: Function,
    selected: [String, Number],
    min: String,
    max: String,
  },
  data: function () {
    return {
      year: null,
      month: null,
      dates: [],
      selectMatrix: {
        row: 0,
        col: 0,
      },
    };
  },
  created: function () {
    var jump_to;

    if (!this.selected) {
      if (this.min) {
        jump_to = this.min;
      } else if (this.max) {
        jump_to = this.max;
      } else {
        jump_to = this.format(new Date());
      }
    } else {
      jump_to = this.selected;
    }
    this.generate(jump_to);
  },
  methods: {
    isSelected: function (n, k) {
      return k == this.selectMatrix.col && n == this.selectMatrix.row
        ? "selected"
        : null;
    },
    onSelect: function (value) {
      this.select(value);
    },
    getMonth: function () {
      return [
        "Január",
        "Február",
        "Március",
        "Április",
        "Május",
        "Június",
        "Július",
        "Augusztus",
        "Szeptember",
        "Október",
        "November",
        "December",
      ][this.month];
    },
    yearback: function () {
      this.year--;
      this.generate();
    },
    yearnext: function () {
      this.year++;
      this.generate();
    },
    monthnext: function () {
      if (this.month > 10) {
        this.month = 0;
        this.year++;
      } else {
        this.month++;
      }
      this.generate();
    },
    monthback: function () {
      if (this.month < 1) {
        this.month = 11;
        this.year--;
      } else {
        this.month--;
      }
      this.generate();
    },
    format: function (date) {
      return (
        date.getFullYear() +
        "." +
        ("0" + (date.getMonth() + 1)).slice(-2) +
        "." +
        ("0" + date.getDate()).slice(-2) +
        "."
      );
    },
    generate: function (jump_to) {
      if (jump_to) {
        if (jump_to.includes("-")) {
          jump_to = jump_to.replaceAll("-", ".") + ".";
        }
        let tmp = jump_to.split(".");
        if (tmp[0]) {
          this.year = tmp[0];
        }
        if (tmp[1]) {
          this.month = parseInt(tmp[1]) - 1;
        }
      }

      var today = this.format(new Date()),
        min,
        max,
        date = new Date(this.year, this.month, 1, 0, 0, 0, 0);
      date.setDate(date.getDate() - ((date.getDay() + 6) % 7));
      if (this.min) min = this.min.replaceAll("-", ".") + ".";
      if (this.max) max = this.max.replaceAll("-", ".") + ".";
      var dates = [];
      while (1) {
        let week = [];
        for (let i = 0; i < 7; i++) {
          let cls = [],
            disabled = false,
            fdate = this.format(date);

          //fókuszálás a kiválasztott napra
          if (jump_to && jump_to == fdate) {
            this.selectMatrix.col = i;
            this.selectMatrix.row = dates.length;
          }

          if (min && fdate < min) {
            disabled = true;
            cls.push("wrongDate");
            cls.push("inactive");
          }
          if (max && fdate > max) {
            disabled = true;
            cls.push("wrongDate");
            cls.push("inactive");
          }

          if (fdate === today) cls.push("today");
          if (date.getMonth() !== this.month) cls.push("inactive");
          week.push({
            text: date.getDate(),
            value: fdate,
            disabled: disabled,
            cls: cls,
          });
          date.setDate(date.getDate() + 1);
        }
        dates.push(week);
        if (date.getMonth() != this.month) break;
      }
      this.dates = dates;
    },
    onKeypress: function (e) {
      if (e.keyCode != 9) e.preventDefault(); //Tabon kívül minden billentyű letíltva
      switch (e.keyCode) {
        // case 13: //enter
        case 32: //space
          var active = this.$el.querySelector("div.pbtn.selected");
          if (active) active.click();
          break;
        case 40: //Down
          if (this.selectMatrix.row + 1 >= this.dates.length) {
            if (this.selectMatrix.col > 1 && this.selectMatrix.col < 5) {
              this.selectMatrix.row = 0;
              // this.set(this.selectMatrix, "row", 0);
            } else {
              // this.set(this.selectMatrix, "row", -1);
              this.selectMatrix.row = -1;
            }
            break;
          }
          // this.set(this.selectMatrix, "row", this.selectMatrix.row + 1);
          this.selectMatrix.row = this.selectMatrix.row + 1;
          break;

        case 38: //Up
          if (this.selectMatrix.row <= -1) {
            // this.set(this.selectMatrix, "row", this.dates.length - 1);
            this.selectMatrix.row = this.dates.length - 1;
            break;
          }
          if (this.selectMatrix.row == 0) {
            switch (this.selectMatrix.col) {
              case 2:
              case 3:
                // this.set(this.selectMatrix, "col", 1);
                this.selectMatrix.col = 1;
                break;
              case 4:
                // this.set(this.selectMatrix, "col", 5);
                this.selectMatrix.col = 5;
                break;
            }
          }
          // this.set(this.selectMatrix, "row", this.selectMatrix.row - 1);
          this.selectMatrix.row = this.selectMatrix.row - 1;
          break;

        case 37: //left
          if (this.selectMatrix.col <= 0) {
            // this.set(this.selectMatrix, "col", 6);
            this.selectMatrix.col = 6;
            break;
          }
          if (this.selectMatrix.row == -1 && this.selectMatrix.col == 5) {
            // this.set(this.selectMatrix, "col", 1);
            this.selectMatrix.col = 1;
          } else {
            // this.set(this.selectMatrix, "col", this.selectMatrix.col - 1);
            this.selectMatrix.col = this.selectMatrix.col - 1;
          }
          break;

        case 39: //Right
          if (this.selectMatrix.col >= 6) {
            // this.set(this.selectMatrix, "col", 0);
            this.selectMatrix.col = 0;
            break;
          }
          if (this.selectMatrix.row == -1 && this.selectMatrix.col == 1) {
            // this.set(this.selectMatrix, "col", 5);
            this.selectMatrix.col = 5;
          } else {
            // this.set(this.selectMatrix, "col", this.selectMatrix.col + 1);
            this.selectMatrix.col = this.selectMatrix.col + 1;
          }
          break;
      }
    },
    obBlur: function () {
      this.up({
        pickerShow: Function,
      }).datepicker = false;
    },
  },
};
</script>

<style>
.datepicker {
  padding: 5px;
}
.datepicker .pbtn {
  width: 32px;
  height: 22px;
  border-width: 1px;
  border-style: solid;
  border-radius: 2px;
}
.datepicker td {
  text-align: center;
}
.datepicker .daybutton.today {
  font-weight: bold;
  text-decoration: underline;
}
.datepicker .daybutton.inactive {
  opacity: 0.5;
}
.datepicker div.pbtn.selected {
  border-width: 2px;
  border-style: solid;
  width: 30px;
  height: 20px;
  opacity: 1;
}
.datepicker {
  font-size: 14px;
  background-color: white;
}

.datepicker .pbtn {
  border-color: #aaa;
}

.datepicker .daybutton {
  background-color: #f9f9fc;
}

.datepicker .daybutton.inactive {
  background-color: #fff;
}

.datepickericontext {
  margin-right: 4px;
}

.datepicker .daybutton.wrongDate {
  border-color: red;
  color: red;
}
</style>
