import axios from 'axios';

// const setAxiosUrl = axios.create({ baseURL: 'http://54.241.70.63:3300/' }); //AWS server URL
// const setAxiosUrl = axios.create({ baseURL: 'http://165.232.187.214:3300/' }); //testing server URL
const setAxiosUrl = axios.create({ baseURL: 'http://192.168.40.153:3300/' }); //development URL

setAxiosUrl.interceptors.request.use((req) => {
  if (localStorage.getItem("token")) {
      req.headers.authorization = `Bearer ${localStorage.getItem("token")}`
  }
  return req;
})

//user sign-up api
export const signUp = async (data) => {
  try {
    const res = await setAxiosUrl.post('/users/sign-up', data);
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};

//user login api
export const signIn = async (data) => {
  try {
    const res = await setAxiosUrl.post('/users/sign-in', data);
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};

//user logout api
export const logOut = async (data) => {
  try {
    const res = await setAxiosUrl.get('/users/log-out');
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};

//get activity data of user
export const activityDetail = async (data) => {
  try {
    const res = await setAxiosUrl.post('/users/activity-detail', data);
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};

//insert activity data
export const insertActivityData = async (data) => {
  try {
    const res = await setAxiosUrl.post('/users/insert-activity-data', {data});
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};

export const trackerData = async () => {
  try {
    const res = await setAxiosUrl.get('/users/tracker');
    return res.data;
  } catch (error) {
    console.log('error', error);
    return error;
  }
};