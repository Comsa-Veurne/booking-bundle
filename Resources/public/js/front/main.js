import '../../scss/front/main.scss';
import store from './store';

import Vue from "vue";
import Form from "./components/Form";
new Vue({
  render: h => h(Form),
  store
}).$mount('#app');