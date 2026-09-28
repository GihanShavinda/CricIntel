import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import {
  api,
} from '../api/client';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

const env = (
  import.meta as ImportMeta & {
    env: Record<
      string,
      string | undefined
    >;
  }
).env;

const reverbKey =
  env.VITE_REVERB_APP_KEY;

const reverbHost =
  env.VITE_REVERB_HOST ??
  '127.0.0.1';

const reverbPort =
  Number(
    env.VITE_REVERB_PORT ??
    8080
  );

const reverbScheme =
  env.VITE_REVERB_SCHEME ??
  'http';

if (!reverbKey) {
  console.error(
    'VITE_REVERB_APP_KEY is missing.'
  );
}

export const echo =
  new Echo({
    broadcaster:
      'reverb',

    key:
      reverbKey,

    wsHost:
      reverbHost,

    wsPort:
      reverbPort,

    wssPort:
      reverbPort,

    forceTLS:
      reverbScheme ===
      'https',

    enabledTransports: [
      'ws',
      'wss',
    ],

    authorizer:
      (
        channel
      ) => ({
        authorize: (
          socketId,
          callback
        ) => {
          api.post(
            '/api/broadcasting/auth',
            {
              socket_id:
                socketId,

              channel_name:
                channel.name,
            }
          )
            .then(
              (
                response
              ) => {
                callback(
                  null,
                  response.data
                );
              }
            )
            .catch(
              (
                error
              ) => {
                console.error(
                  'Private channel authorization failed:',
                  {
                    channel:
                      channel.name,

                    status:
                      error
                        ?.response
                        ?.status,

                    response:
                      error
                        ?.response
                        ?.data,

                    error,
                  }
                );

                callback(
                  error instanceof Error
                    ? error
                    : new Error(
                        'Channel authorization failed'
                      ),
                  null
                );
              }
            );
        },
      }),
  });