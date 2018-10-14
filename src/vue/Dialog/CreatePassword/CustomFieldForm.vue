<template>
    <div class="custom-field-form">
        <span class="field-id">#{{id}}</span>
        <input type="text" :placeholder="namePlaceholder" class="field-name" v-model="name" maxlength="48" :class="{error:showNameError}"/>
        <input type="button" class="fa fa-trash field-button" @click="deleteField" :disabled="!isValidName" value=""/>
        <select class="field-type" v-model="type" :disabled="!isValidName">
            <translate tag="option" value="text">Text</translate>
            <translate tag="option" value="secret">Secret</translate>
            <translate tag="option" value="email">Email</translate>
            <translate tag="option" value="url">Link</translate>
            <translate tag="option" value="file">File</translate>
        </select>
        <icon tag="label" icon="upload" class="file-icon" v-if="showFilePicker"/>
        <input class="file-picker" type="button" @click="openNextcloudFile" v-if="showFilePicker" :disabled="!isValidName" :style="getFileButtonStyle" :value="value"/>
        <icon tag="label" icon="pencil" class="file-icon" v-if="!showFilePicker"/>
        <input class="field-value"
               :type="getFieldType"
               :placeholder="valuePlaceholder"
               v-model="value"
               maxlength="320"
               v-if="!showFilePicker"
               :disabled="!isValidName"
               :pattern="getPattern"
               required/>
    </div>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';
    import Translate from '@vue/Components/Translate';
    import SettingsManager from '@js/Manager/SettingsManager';
    import Icon from "@/vue/Components/Icon";

    export default {
        name      : 'custom-field-form',
        components: {Icon, Translate},
        props     : {
            field     : {
                type     : Object,
                'default': () => {
                    return {
                        name : '',
                        type : 'text',
                        value: null
                    };
                }
            },
            takenNames: {
                type     : Array,
                'default': () => {
                    return [];
                }
            },
            id        : {
                type: Number
            },
            isBlank   : {
                type     : Boolean,
                'default': false
            }
        },
        data() {
            return {
                name        : this.field.name,
                type        : this.field.type,
                value       : this.field.value,
                originalName: this.field.name
            };
        },

        computed: {
            showNameError() {
                return !this.isValidName && !this.isBlank;
            },
            isValidName() {
                return this.name.length && (this.takenNames.indexOf(this.name) === -1 || this.originalName === this.name);
            },
            getFieldType() {
                if(this.type === 'secret') return 'password';
                if(this.type === 'email') return 'email';
                return 'text';
            },
            showFilePicker() {
                return this.type === 'file';
            },
            getFileButtonStyle() {
                return {
                    backgroundImage: `url(${SettingsManager.get('server.theme.folder.icon')})`
                };
            },
            getPattern() {
                if(this.type === 'url') return '\\w+:\/\/.+';
                if(this.type === 'email') return '[\\w\\._-]+@.+';
                return false;
            },
            namePlaceholder() {
                return Localisation.translate('Name');
            },
            valuePlaceholder() {
                return Localisation.translate('Value');
            }
        },
        methods : {
            async openNextcloudFile() {
                this.value = await Messages.filePicker();
            },
            deleteField() {
                this.$emit('deleted', this.name);
            },
            updateField() {
                if(!this.value) return;

                this.$emit(
                    'updated',
                    {
                        name        : this.name,
                        type        : this.type,
                        value       : this.value,
                        originalName: this.originalName
                    }
                );
                this.originalName = this.name;
            }
        },

        watch: {
            name() {
                if(this.isValidName) this.updateField();
            },
            type(value) {
                if(value === 'file') this.value = null;
                if(this.isValidName) this.updateField();
            },
            value() {
                if(this.isValidName) this.updateField();
            },
            isBlank(newValue, oldValue) {
                if(newValue && newValue !== oldValue) {
                    this.name = '';
                    this.type = 'text';
                    this.value = '';
                }
            },
            field(newValue) {
                if(newValue.name !== this.name) {
                    this.name = newValue.name;
                    this.originalName = newValue.name;
                }
                if(newValue.type !== this.type) this.type = newValue.type;
                if(newValue.value !== this.value) this.value = newValue.value;
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields .custom-field-form {
        display               : grid;
        grid-template-areas   : "id name type delete" "icon value value value";
        grid-template-columns : 1.5rem 1fr 7.5rem 3rem;

        .field-id {
            padding     : 1rem 0;
            font-weight : bold;
            font-size   : 1rem;
        }

        .field-name {
            grid-area : name;
            max-width : none;
            width     : 100%;

            &.error {
                border : 1px solid $color-red;
            }
        }
        .file-icon {
            grid-area   : icon;
            padding     : 1rem 0;
            line-height : 1rem;
            font-size   : 1rem;
            text-align  : center;
        }
        .field-type {
            grid-area : type;
            max-width : none;
        }

        .field-value,
        .file-picker {
            grid-area : value;
            max-width : none;
            width     : 100%;
        }

        .file-picker {
            background    : no-repeat center;
            padding       : 0 25px;
            overflow      : hidden;
            text-overflow : ellipsis;

            &[value] {
                background-position : 5px center;
            }
        }

        .field-button {
            grid-area : delete;
            max-width : none;
        }

        @media all and (min-width : $width-medium) and (max-width : $width-large) {
            .field-type {
                width : 22%;
            }

            .field-value,
            .file-picker {
                width : 89%;
            }

            .field-button {
                width : 8%;
            }
        }

        @media all and (max-width : $width-small) {
            .field-type {
                width : 22.5%;
            }

            .field-value,
            .file-picker {
                width : 92%;
            }
        }

        @media all and (max-width : $width-extra-small) {
            .field-type {
                width : 21%;
            }

            .field-value,
            .file-picker {
                width : 85%;
            }

            .field-button {
                width : 12%;
            }
        }
    }
</style>