import axios from 'axios';

let get = {
  getDisabledDates() {
    return axios.get('/booking/disabled-dates');
  },
  getOptions(date) {
    return axios.post('/booking/options', {
      date
    });
  }
};

let post = {
  createBooking(booking) {
    return axios.post('/booking', booking);
  }
};

export default {
  get,
  post
}
