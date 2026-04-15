import React from 'react';
import logo from '../../../assets/marro-logo.png';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import './titlebar.css';
import {
  faXmark,
  faWindowMinimize,
  faWindowMaximize,
  faSquare,
} from '@fortawesome/free-solid-svg-icons';
import { Button } from 'react-bootstrap';
const { ipcRenderer } = window.electron;

const Titlebar = () => {

  //send message to main file.
  const closeBtn = () => {
    ipcRenderer.sendMessage('close-btn', '');
  };

  const minimizeBtn = () => {
    ipcRenderer.sendMessage('minimize-btn', '');
  };

  const maximizeBtn = () => {
    ipcRenderer.sendMessage('maximize-btn', '');
  };

  const doubleClickFun=()=>{
    ipcRenderer.sendMessage('maximize-btn', '');
  }

  return (
    <div className="bg-light d-flex justify-content-between " onDoubleClick={()=>doubleClickFun()}>
      <div className="logo d-flex align-items-center ms-2">
        <img src={logo} alt="logo" style={{ width: '35px' }} />
        <p className="text-dark mb-0 ms-3 fw-bold whitespace-nowrap">Marro App Tracker</p>
      </div>
      <div className='w-100 titlebar'></div>
      <div className="icon d-flex align-items-center">
        <div className="btn-div"  onClick={() => minimizeBtn()}>
          <FontAwesomeIcon
            icon={faWindowMinimize}
            className="text-black fs-6 pb-2"
          />
        </div>
        <div className="ps-2 btn-div" onClick={() => maximizeBtn()}>
          <span className="btn-maxi"> </span>
        </div>
        <div className="last-btn btn-div" onClick={() => closeBtn()}>
          <FontAwesomeIcon icon={faXmark} className="text-black fs-5" />
        </div>
      </div>
    </div>
  );
};

export default Titlebar;
