import React, { useEffect, useState } from 'react';
import { Button, Col , Row} from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { toastStyle } from './ToastStyle';

const Tracker = () => {
  const navigate = useNavigate();
  let second :any= Number(localStorage.getItem('second')) ?? 0;
  let minute :any=  Number(localStorage.getItem('minute')) ?? 0;
  let hr :any= Number(localStorage.getItem('hour')) ?? 0;
  const [sec, setSec] = useState(second);
  const [min, setMin] = useState(minute);
  const [hour, setHour] = useState(hr);
  const [intervalTime , setIntervalTime]=useState(localStorage.getItem('interval') ?? 'true');
  let interval: any;

  const intervalClearFunction =()=>{
    return clearInterval(interval);
  }

  const stop = () => {
    intervalClearFunction();
    second = 0;
    minute = minute+1;
    setSec(second);
    setMin((minute));
    localStorage.setItem('second',second)
    localStorage.setItem('minute',minute)
    Hello();
  };

  const stopMinute =()=>{
    intervalClearFunction()
    second=0;
    minute=0;
    hr = hr+1;
    setSec(second);
    setMin(minute);
    setHour(hr);
    localStorage.setItem('second',second)
    localStorage.setItem('minute',minute)
    localStorage.setItem('hour',hr)
    Hello();
  }

  const Hello = () => {
    interval = setInterval(() => {
      if(localStorage.getItem('interval') === 'true'){
        if (second <= 59) {
          setSec((second += 1));
          localStorage.setItem('second',second)
        }
        else if (second === 60) {
          stop();
        }
        if(minute == 60){
          stopMinute();
        }
      }

    }, 1000);
  };

  const stopTracker = ()=>{
    intervalClearFunction();
    setIntervalTime('false')
    toast.info('Your script is stopped...!', toastStyle)
    localStorage.setItem('interval','false');
  }

  const startTracker = ()=>{
    intervalClearFunction();
    setIntervalTime('true')
    toast.info('Your script is started...!', toastStyle)
    localStorage.setItem('interval','true');
  }

  const logout=()=>{
    console.log('logout');
    setIntervalTime('false')
    intervalClearFunction();
    toast.success('Your script is stopped...!', toastStyle)
    localStorage.setItem('interval','false');
    localStorage.clear();
    navigate('/');
  }

  useEffect(() => {
    localStorage.setItem('interval',intervalTime)
    Hello();
  }, [intervalTime]);

  return (
    <div className="text-center">
      <h2>Tracker</h2>
      <Row className="d-flex justify-content-center">
        <Col sm={4} className="p-3 border">
          <p>Hour</p>
          <h3>{hour}</h3>
        </Col>

        <Col sm={4} className="p-3 border">
          <p>Minute</p>
          <h3>{min}</h3>
        </Col>

        <Col sm={4} className="p-3 border">
          <p>Second</p>
          <h3>{sec}</h3>
        </Col>
      </Row>
      {
        localStorage.getItem('interval') == 'true' ?
        <Button className='w-100 mt-3 mx' onClick ={()=>{
          stopTracker()
          }}>Stop</Button>
          :
          <Button className='w-100 mt-3 mx' onClick ={()=>{
            startTracker()
            }}>Start</Button>
      }

        {/* <Button className='w-100 mt-3 mx' onClick={()=>{logout()}}>Logout</Button> */}
        {/* <Button className='w-100 mt-3 mx' onClick={()=>{navigate('/profile')}}>Profile</Button> */}
    </div>
  );
};

export default Tracker;
