<template>
  <div class="container">
    <h1>Booking</h1>
    <div class="form-group" v-if="step === 1">
      <label for="amountPersons">Aantal personen</label>
      <input type="number" class="form-control" min="1" max="12" v-model="booking.amountPersons">
    </div>
    <div class="form-group" v-if="step === 2">
      <label for="date">Selecteer datum</label>
      <datepicker
        :language="picker.nl"
        :disabledDates="picker.disabledDates"
        inputClass="form-control"
        v-model="booking.date"
      ></datepicker>
    </div>
    <div class="form-group" v-if="step === 3">
      <label for="options">Opties</label>
      <div class="form-check" v-for="option in options">
        <input class="form-check-input" type="checkbox" :value="option.id" :id="'options_' + option.id" v-model="booking.options">
        <label class="form-check-label" :for="'options_' + option.id">
          {{ option.name }} - {{ option.price }}
        </label>
      </div>
    </div>
    <div class="form-group" v-if="step === 4">
      <div class="form-group">
        <label for="name">Naam</label>
        <input type="text" class="form-control" v-model="booking.information.name" id="name">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" class="form-control" v-model="booking.information.email" id="email">
      </div>
      <div class="form-group">
        <label for="street">Straat</label>
        <input type="text" class="form-control" v-model="booking.information.street" id="street">
      </div>
    </div>
    <div v-if="step === 5">
      <div class="alert alert-success">
        Bedankt!
      </div>
    </div>
    <button type="button" @click="step++" class="btn btn-primary" v-if="step <= maxSteps">{{ step === maxSteps ? 'Boek nu' : 'Volgende' }}</button>
  </div>
</template>

<script>
  import Datepicker from 'vuejs-datepicker';
  import { nl } from 'vuejs-datepicker/dist/locale';
  import moment from 'moment';

  export default {
    name: "Form",
    components: {
      Datepicker
    },
    created() {
      this.$store.dispatch('getDisabledDates');
    },
    computed: {
      disabledDates() {
        return this.$store.getters['getDisabledDates'];
      },
      options() {
        return this.$store.getters['getOptions']
      }
    },
    watch: {
      step() {
        switch (this.step) {
          case 3:
            this.$store.dispatch('getOptions', this.booking.date);
            break;
          case 5:
            this.save();
        }
      }
    },
    methods: {
      save() {
        this.$store.dispatch('createBooking', this.booking);
      }
    },
    data() {
      return {
        booking: {
          amountPersons: 1,
          date: moment().add(1, 'days').toDate(),
          options: [],
          information: {}
        },
        step: 1,
        maxSteps: 4,
        picker: {
          nl: nl,
          disabledDates: {
            to: moment().toDate(),
            customPredictor: (date) => {
              return this.disabledDates.find(disabledDate => {
                return moment(disabledDate).isSame(moment(date), 'day');
              });
            }
          }
        }
      }
    }
  }
</script>

<style scoped>

</style>