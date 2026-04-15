import { contextBridge, ipcRenderer, IpcRendererEvent } from 'electron';
import {
  signIn,
  signUp,
  trackerData,
  logOut,
  insertActivityData,
  activityDetail,
} from './API/apiConfig';
export type Channels = 'ipc-example';

const nodeApi = {
  signUp: (data: any) => signUp(data),
  signIn: (data: any) => signIn(data),
  trackerData: () => trackerData(),
  logOut: () => logOut(),
  activityDetail: (data: any) => activityDetail(data),
  insertActivityData: (data: any) => insertActivityData(data),
};

contextBridge.exposeInMainWorld('electron', {
  nodeApi,

  ipcRenderer: {
    sendMessage(channel: Channels, args: unknown[]) {
      ipcRenderer.send(channel, args);
    },
    on(channel: Channels, func: (...args: unknown[]) => void) {
      const subscription = (_event: IpcRendererEvent, ...args: unknown[]) =>
        func(...args);
      ipcRenderer.on(channel, subscription);

      return () => ipcRenderer.removeListener(channel, subscription);
    },
    once(channel: Channels, func: (...args: unknown[]) => void) {
      ipcRenderer.once(channel, (_event, ...args) => func(...args));
    },

    invoke: (channel: Channels, args: unknown[]) => {
      return ipcRenderer.invoke(channel, args);
    },
  },
});

