import React, { useEffect, useState } from 'react';
import {
  Button,
  Container,
  Form,
  Navbar,
  Table,
  Spinner,
} from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { toastStyle } from './ToastStyle';
import moment from 'moment';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faUser,
  faRefresh,
} from '@fortawesome/free-solid-svg-icons';
import Tracker from './Tracker';

const { logOut, activityDetail, insertActivityData } = window.electron.nodeApi;
const { ipcRenderer } = window.electron;

const Home = () => {
  const [getData, setGetData] = useState('');
  const [detailData, setDetailData] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const email = localStorage.getItem('email');

  //get user activity all data from api
  const dataFun = async (data: any) => {
    const res = await activityDetail({ dateString: data });
    if (res.status == 1) {
      setDetailData(res.data);
      setLoading(false);
    } else {
      setLoading(false);
    }
  };

  useEffect(() => {
    setLoading(true);
    setTimeout(() => {
      dataFun(getData);
    }, 3300);
  }, [getData]);

  const onChangeHandler = (e: any) => {
    setGetData(e.target.value);
  };

  const ReloadPage = () => {
    setLoading(true);
    setTimeout(() => {
      dataFun(getData);
    }, 3300);
  };

  var trackerInterval: any;
  const Interval = () => {
    //call event from main file to get json data of 
    ipcRenderer.on('read-file', async (arg) => {
      const data = await insertActivityData(arg);
    });
    trackerInterval = setInterval(() => {
      //send message to main file
      ipcRenderer.sendMessage('read-file', email);
    }, 3000);
  };

  useEffect(() => {
    Interval();
  }, []);

  ipcRenderer.on('log-out-on-quit', async (event, ...args) => {
    if (email) {
      await logOutFun();
    }
  });

  const clearIntervalFunction = () => {
    clearInterval(trackerInterval);
  };

  const logOutFun = async () => {
    const res: any = await logOut();
    if (res?.status == 1) {
      //send message from main page
      ipcRenderer.sendMessage('clear-interval', email);
      clearIntervalFunction();
      localStorage.clear();
      window.location.reload();
      localStorage.setItem('logout-msg', 'true');
    } else {
      toast.error('Please try again', toastStyle);
    }
  };

  return (
    <>
      <div className="logout-page ">
        <Navbar expand="lg" className="navbarbg ">
          <Container fluid className="justify-content-between">
            <Navbar.Text className="d-flex align-items-center">
              <FontAwesomeIcon icon={faUser} className="text-black fs-5 me-3" />
              <h3 className="mb-0">{email}</h3>
            </Navbar.Text>
            <Navbar.Brand>
              <button
                type="submit"
                className="submit-btn "
                onClick={() => logOutFun()}
              >
                Log Out
              </button>
            </Navbar.Brand>
          </Container>
        </Navbar>

        {/* <Tracker /> */}

        <div className="container container-div">
          <div className="table-box">
            <div className="table-div ">
              <div className="table-header d-flex justify-content-between align-items-center">
                <div>
                  <h2 className="text-dark mb-0">Salesforce Activity Data</h2>
                </div>
                <div className="d-flex gap-2" onChange={onChangeHandler}>
                  <Button
                    className="bg-transparent border-0 text-dark"
                    style={{ boxShadow: 'none' }}
                    title="Reload Page"
                    onClick={() => ReloadPage()}
                  >
                    <FontAwesomeIcon icon={faRefresh} className="fs-3" />
                  </Button>
                  <Form.Select aria-label="Default select example">
                    <option value="">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="lastWeek">Last 7 days</option>
                  </Form.Select>
                </div>
              </div>

              <Table className=" table text-center table-striped" responsive>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Session Duration</th>
                    <th>Salesforce</th>
                  </tr>
                </thead>
                {loading ? (
                  <tbody>
                    <tr>
                      <td colSpan={6} className="text-center p-5">
                        <Spinner animation="border" role="status">
                          <span className="visually-hidden text-center">
                            Loading...
                          </span>
                        </Spinner>
                      </td>
                    </tr>
                  </tbody>
                ) : (
                  <tbody>
                    {detailData && detailData.length > 0 ? (
                      detailData?.map((e: any, index) => (
                        <tr key={index}>
                          <td>{index + 1}</td>
                          <td>{moment(e?.date).format('M/D/YYYY')}</td>
                          <td>{moment(e?.login).format('h:mm:ss A')}</td>
                          <td>
                            {e?.logout
                              ? moment(e?.logout)?.format('h:mm:ss A')
                              : 'Current Session'}
                          </td>
                          <td>
                            {e.logout
                              ? moment
                                  .utc(
                                    moment(
                                      moment(e?.logout).format('h:mm:ss A'),
                                      'h:mm:ss A'
                                    ).diff(
                                      moment(
                                        moment(e?.login).format('h:mm:ss A'),
                                        'h:mm:ss A'
                                      )
                                    )
                                  )
                                  .format('H:mm:ss')
                              : getData == ''
                              ? moment
                                  .utc(
                                    moment(
                                      moment(e?.newDate).format('h:mm:ss A'),
                                      'h:mm:ss A'
                                    ).diff(
                                      moment(
                                        moment(e?.login).format('h:mm:ss A'),
                                        'h:mm:ss A'
                                      )
                                    )
                                  )
                                  .format('H:mm:ss')
                              : moment
                                  .utc(
                                    moment(
                                      moment(new Date()).format('h:mm:ss A'),
                                      'h:mm:ss A'
                                    ).diff(
                                      moment(
                                        moment(e?.login).format('h:mm:ss A'),
                                        'h:mm:ss A'
                                      )
                                    )
                                  )
                                  .format('H:mm:ss')}
                          </td>
                          <td>
                            {e?.totalTime
                              ? moment
                                  .utc(e?.totalTime * 1000)
                                  .format('H:mm:ss')
                              : '0:00:00'}
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr className="text-center p-5">
                        <td colSpan={6} className=" text-dark">
                          No data found...!
                        </td>
                      </tr>
                    )}
                  </tbody>
                )}
              </Table>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Home;
