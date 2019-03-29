import Vue from 'vue';
import Vuex from 'vuex';
import api from '../api/main';

Vue.use(Vuex);

export default new Vuex.Store({
  getters: {
    getDisabledDates(state) {
      return state.disabledDates;
    },
    getOptions(state) {
      return state.options;
    },
    getBooking(state) {
      return state.booking;
    }
  },
  state: {
    disabledDates: [],
    options: [],
    booking: {}
  },
  mutations: {
    setDisabledDates(state, disabledDates) {
      state.disabledDates = disabledDates;
    },
    setOptions(state, options) {
      state.options = options;
    },
    setBooking(state, booking) {
      state.booking = booking;
    }
  },
  actions: {
    async getDisabledDates({ commit }) {
      let response = await api.get.getDisabledDates();
      commit('setDisabledDates', response.data);
    },
    async getOptions({ commit }, date) {
      let response = await api.get.getOptions(date);
      commit('setOptions', response.data);
    },
    async createBooking({ commit }, booking) {
      let response = await api.post.createBooking(booking);
      commit('setBooking', response.data);
    }
  }
});
