import SettingsService from '@js/Services/SettingsService';

class DeferredActivationService {

    /**
     *
     */
    constructor() {
        this._version = process.env.APP_VERSION;
        this._app = process.env.APP_NAME;
        this._features = null;
    }

    /**
     *
     * @param id
     * @param ignoreNightly
     * @returns {Promise<boolean>}
     */
    async check(id, ignoreNightly = false) {
        if(!ignoreNightly && process.env.NIGHTLY_FEATURES) return true;

        let features = await this.getFeatures();
        if(features.hasOwnProperty(id)) return features[id] === true;

        return false;
    }

    /**
     *
     * @returns {Promise<object>}
     */
    async getFeatures() {
        if(this._features !== null) return this._features;

        this._features = {};
        let features = document.querySelector('meta[name=pw-features]');
        if(features) {
            this._features = JSON.parse(features.getAttribute('content'));
            return this._features;
        }

        let url = SettingsService.get('server.handbook.url') + '_features/features-v1.json';

        try {
            let response = await fetch(new Request(url, {credentials: 'omit', referrerPolicy: 'no-referrer'}));
            if(response.ok) {
                let data = await response.json();
                this._processFeatures(data);
            }
        } catch(e) {
            console.error(e);
        }

        return this._features;
    }

    /**
     *
     * @param json
     * @private
     */
    _processFeatures(json) {
        if(!json.hasOwnProperty(this._app)) return;
        let mainVersion = this._version.substr(0, this._version.lastIndexOf('.')),
            appFeatures = json[this._app];

        if(appFeatures.hasOwnProperty(mainVersion)) {
            this._features = appFeatures[mainVersion];
        }

        if(appFeatures.hasOwnProperty(this._version)) {
            let versionFeatures = appFeatures[this._version];

            for(let key in versionFeatures) {
                if(versionFeatures.hasOwnProperty(key)) {
                    this._features[key] = versionFeatures[key];
                }
            }
        }
    }
}

export default new DeferredActivationService();