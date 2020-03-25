import { ErrorHandler, isDevMode } from '@angular/core';
import { Injectable } from '@angular/core';

// services
import { SecureHttpService } from 'services/http';

@Injectable()
export class AppErrorHandlerService implements ErrorHandler {
    /**
     * Constructor
     */
    constructor(private http: SecureHttpService) {}

    /**
     * Is dev mode
     */
    isDevMode(): boolean {
        return isDevMode();
    }

    /**
     * Handle error
     */
    handleError(error: any): void {
        if (window["behatErrors"]) {
            window["behatErrors"].push({
                message: error.toString(),
                location:  error.stack ? error.stack.toString() : ''
            });
        }
        // save error
        if (!this.isDevMode()) {
            this.http.post('/logs', {
                message: error.toString()
            }, {}, false).subscribe();

            return;
        }

        console.error(error);
    }
}
