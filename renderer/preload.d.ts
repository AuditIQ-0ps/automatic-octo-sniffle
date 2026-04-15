import { Channels } from 'main/preload';

declare global {
  interface Window {
    electron: {
      ipcRenderer: {
        sendMessage(channel: Channels, args: unknown[]): void;
        on(
          channel: string,
          func: (...args: unknown[]) => void
        ): (() => void) | undefined;
        once(channel: string, func: (...args: unknown[]) => void): void;
      };

      nodeApi : {
        signIn : (data:any)=>any,
        signUp : (data:any)=>any,
        trackerData : ()=>any,
        logOut:()=>any,
        insertActivityData:(data:any)=>any,
        activityDetail:(data:any)=>any,
      };

    };
  }
}

export {};
