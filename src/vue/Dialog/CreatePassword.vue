<template>
    <div class="background" id="passwords-create-new">
        <div class="window">
            <div class="title" :style="getTitleStyle">
                <icon icon="star" class="favorite" :class="{active:password.favorite}" title="Mark as favorite" @click.native="toggleFavorite"/>
                <field type="text" placeholder="Name" name="label" maxlength="64" v-model="password.label" :style="getTitleStyle"/>
                <icon icon="times" class="close" title="Close window" @click.native="closeWindow"/>
            </div>
            <div class="content">
                <div class="properties-base">
                    <icon tag="label" for="password-username" icon="user"/>
                    <field type="text" id="password-username" placeholder="Username" name="username" maxlength="64" v-model="password.username"/>
                    <icon tag="label" for="password-password" icon="key"/>
                    <div class="password-container">
                        <div class="password-field">
                            <div class="icons">
                                <translate tag="i" class="fa" :class="{ 'fa-eye': showPassword, 'fa-eye-slash': !showPassword }" @click="togglePasswordVisibility()" title="Toggle visibility"/>
                                <translate tag="i" class="fa fa-refresh" :class="{ 'fa-spin': showLoader }" @click="generateRandomPassword()" title="Generate password"/>
                            </div>
                            <field :type="showPassword ? 'text':'password'"
                                   id="password-password"
                                   placeholder="Password"
                                   name="password"
                                   pattern=".{0,256}"
                                   v-model="password.password"
                                   required
                                   readonly/>
                        </div>
                        <div class="settings" :class="{active: generator.active}">
                            <input id="password-password-numbers" type="checkbox" v-model="generator.numbers" :disabled="!generator.active"/>
                            <translate tag="label" for="password-password-numbers" say="Numbers"/>
                            <input id="password-password-special" type="checkbox" v-model="generator.special" :disabled="!generator.active"/>
                            <translate tag="label" for="password-password-special" say="Special Characters"/>
                            <translate tag="label" for="password-password-strength" say="Strength"/>
                            <select id="password-password-strength" v-model="generator.strength">
                                <option>1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="properties-extra">
                    <icon tag="label" for="password-url" icon="globe"/>
                    <field type="text" id="password-url" placeholder="Website" name="url" maxlength="2048" v-model="password.url"/>
                    <icon tag="label" for="password-tags" icon="tags"/>
                    <tags :password="password"/>
                </div>

                <div class="foldouts">
                    <foldout title="Notes" :initially-open="notesOpen">
                        <div class="notes-container">
                            <translate tag="div" class="warning" say="You have reached the maximum length of 4096 characters" v-if="password.notes.length > 4095"/>
                            <textarea id="password-notes" name="notes" maxlength="4096"></textarea>
                        </div>
                    </foldout>
                    <foldout title="Custom Fields">
                        <custom-fields :fields="password.customFields" @updated="updateCustomFields"/>
                    </foldout>
                </div>
                <div class="controls">
                    <translate tag="input" type="submit" value="Save"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import API from "@js/Helper/api";
    import Foldout from '@vc/Foldout';
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';
    import EnhancedApi from '@js/ApiClient/EnhancedApi';
    import ThemeManager from '@js/Manager/ThemeManager';
    import CustomFields from '@vue/Dialog/CreatePassword/CustomFields';
    import Field from '@vc/Form/Field';
    import Icon from '@vc/Icon';
    import Tags from "@/vue/Components/Tags";

    export default {
        data() {
            return {
                title       : 'Create password',
                notesOpen   : window.innerWidth > 641,
                showPassword: false,
                showLoader  : false,
                simplemde   : null,
                generator   : {numbers: undefined, special: undefined, strength: 0, active: false},
                password    : {favorite: false, cseType: 'none', notes: '', customFields: {}},
                _success    : null
            };
        },

        components: {
            Tags,
            Icon,
            Field,
            Foldout,
            Translate,
            CustomFields
        },

        mounted() {
            this.loadSimpleMde();
            document.getElementById('password-username').focus();
            setTimeout(() => {document.getElementById('password-password').removeAttribute('readonly');}, 250);
        },

        computed: {
            getTitleStyle() {
                return {
                    color          : ThemeManager.getContrastColor(),
                    backgroundColor: ThemeManager.getColor()
                };
            }
        },

        methods: {
            toggleFavorite() {
                this.password.favorite = !this.password.favorite;
            },
            closeWindow() {
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
            },
            togglePasswordVisibility() {
                this.showPassword = !this.showPassword;
            },
            generateRandomPassword() {
                if(this.showLoader) return;
                this.showLoader = true;
                let numbers = undefined, special = undefined, strength = undefined;

                if(this.generator.active) {
                    strength = this.generator.strength;
                    numbers = this.generator.numbers;
                    special = this.generator.special;
                }

                API.generatePassword(strength, numbers, special)
                   .then((d) => {
                       this.password.password = d.password;
                       if(this.generator.active === false) {
                           this.generator = {numbers: d.numbers, special: d.special, strength: d.strength, active: true};
                       }
                       this.showPassword = true;
                       this.showLoader = false;
                   })
                   .catch(() => {
                       this.showLoader = false;
                   });
            },
            updateCustomFields($event) {
                this.password.customFields = $event;
            },
            submitAction() {
                let password = Utility.cloneObject(this.password);
                password = EnhancedApi.flattenPassword(password);
                password = EnhancedApi.validatePassword(password);

                if(this._success) {
                    try {
                        this._success(password);
                        this.closeWindow();
                    } catch(e) {
                        console.error(e);
                    }
                }
            },
            async loadSimpleMde() {
                try {
                    let SimpleMDE = await import(/* webpackChunkName: "simplemde" */ 'simplemde');

                    this.simplemde = new SimpleMDE(
                        {
                            element                : document.getElementById('password-notes'),
                            hideIcons              : ['fullscreen', 'side-by-side'],
                            autoDownloadFontAwesome: false,
                            spellChecker           : false,
                            placeholder            : Localisation.translate('Take some notes'),
                            forceSync              : true,
                            initialValue           : this.password.notes,
                            status                 : [
                                {
                                    defaultValue: (el) => {el.innerHTML = this.password.notes.length + '/4096';},
                                    onUpdate    : (el) => {el.innerHTML = this.password.notes.length + '/4096';}
                                }
                            ]
                        }
                    );
                    this.simplemde.codemirror.on('change', () => {
                        let value = this.simplemde.value();
                        if(value.length > 4096) {
                            value = value.substring(0, 4096);
                            this.simplemde.value(value);
                        }
                        this.password.notes = value;
                    });
                } catch(e) {
                    console.error(e);
                    Messages.alert(['Unable to load {module}', {module: 'SimpleMde'}], 'Network error');
                }
            }
        },

        watch: {
            password(password) {
                if(typeof password.customFields === "string") password.customFields = JSON.parse(password.customFields);
                if(password.customFields === null) password.customFields = {};
                if(this.simplemde) this.simplemde.value(password.notes);
            },
            'generator.numbers'(value, oldValue) {
                if(oldValue !== undefined) this.generateRandomPassword();
            },
            'generator.special'(value, oldValue) {
                if(oldValue !== undefined) this.generateRandomPassword();
            },
            'generator.strength'(value, oldValue) {
                if(oldValue !== undefined) this.generateRandomPassword();
            }
        }
    };
</script>

<style lang="scss">
    @import "~simplemde/dist/simplemde.min.css";

    #passwords-create-new {
        &.background {
            position         : fixed;
            top              : 0;
            left             : 0;
            width            : 100%;
            height           : 100%;
            background-color : rgba(0, 0, 0, 0.7);
            z-index          : 3001;

            .window {
                position              : fixed;
                top                   : 6%;
                left                  : 15%;
                width                 : 70%;
                height                : 88%;
                z-index               : 9999;
                overflow              : hidden;
                background-color      : $color-white;
                border-radius         : 3px;
                box-sizing            : border-box;
                display               : grid;
                grid-template-columns : 100%;
                grid-template-areas   : "title" "content";
                grid-template-rows    : 3.25rem auto;
                justify-items         : stretch;
                align-items           : stretch;

                .title {
                    grid-area             : title;
                    display               : grid;
                    grid-template-columns : 2.5rem 1fr 3rem;
                    font-size             : 1.25rem;

                    .favorite {
                        cursor     : pointer;
                        transition : color 0.15s ease-in-out;
                        padding    : 1rem .75rem;

                        &.active {
                            color : $color-yellow;
                        }
                    }

                    input {
                        font-size  : 1.5rem;
                        width      : 100%;
                        padding    : .5rem .25rem;
                        background : transparent;
                    }

                    .close {
                        cursor  : pointer;
                        padding : 1rem;
                    }
                }

                .content {
                    grid-area : content;
                    overflow  : auto;
                }

                @media (max-width : $width-medium) {
                    border-radius : 0;
                    top           : 0;
                    left          : 0;
                    bottom        : 0;
                    right         : 0;
                    width         : 100%;
                    height        : 100%;
                }
            }
        }

        select,
        input {
            font-size        : 1rem;
            font-weight      : 300;
            border           : none;
            background-color : transparent;
        }

        select {
            padding: .5rem;
            height: auto;
        }

        .content {
            display               : grid;
            grid-template-columns : 1fr 1fr;
            grid-template-areas   : "base extra" "foldout foldout" ". controls";
            grid-column-gap       : 2rem;
            grid-template-rows    : min-content;
            padding               : 15px;

            .properties-base,
            .properties-extra {
                grid-area             : base;
                display               : grid;
                grid-template-columns : 1.5rem 1fr;
                font-size             : 1rem;
                grid-template-rows    : 2rem 6rem;
                grid-row-gap          : 1rem;

                label {
                    padding : .5rem .5rem .5rem 0;
                }

                input {
                    width   : 100%;
                    margin  : 0;
                    padding : 0.25rem;

                    &[type=checkbox] {
                        width   : auto;
                        display : inline-block;
                    }
                }

                .password-field {
                    display  : block;
                    width    : 100%;
                    position : relative;

                    input {
                        max-width     : initial;
                        padding-right : 45px;

                        &[type=text] {
                            font-family : 'Lucida Console', 'Lucida Sans Typewriter', 'DejaVu Sans Mono', monospace;
                            font-weight : 300;
                        }
                    }

                    .icons {
                        position    : absolute;
                        top         : 0;
                        right       : 3px;
                        bottom      : 0;
                        display     : flex;
                        align-items : center;

                        i {
                            font-size  : 1rem;
                            cursor     : pointer;
                            margin     : 3px;
                            opacity    : .25;
                            transition : opacity .1s ease-in-out;

                            &:hover {
                                opacity : 1;
                            }
                        }
                    }
                }

                .settings {
                    grid-column-start : 2;
                    grid-column-end   : 3;
                    line-height       : 30px;
                    display           : flex;
                    overflow          : hidden;
                    max-height        : 0;
                    transition        : max-height 0.25s ease-in-out;

                    &.active {
                        max-height : 60px;
                    }

                    input {
                        margin : 0;
                    }

                    label {
                        padding     : 0 .5rem;
                        font-weight : 300;
                        line-height : 2.5rem;
                    }
                }
            }

            .properties-extra {
                grid-area : extra;

                .tags-container {
                    font-weight : 300;

                    .tag {
                        padding : 0.25rem;
                    }

                    input {
                        width : auto;
                    }
                }
            }

            .foldouts {
                grid-area : foldout;

                .notes-container {
                    border        : 1px solid #dbdbdb;
                    border-radius : 3px;
                    transition    : border-color .10s ease-in-out;
                    margin-bottom : 1px;

                    &:hover {
                        border-color : #0082c9;
                    }

                    .editor-toolbar {
                        padding       : 0;
                        border-top    : none;
                        border-left   : none;
                        border-right  : none;
                        opacity       : 1;
                        margin-top    : 0;
                        margin-bottom : 0;

                        &:before {
                            line-height : 0;
                        }

                        a {
                            padding   : 0 .5rem;
                            font-size : 1rem;
                            width     : auto;
                            height    : auto;
                            border    : none;

                            &:hover {
                                background-color : transparent;
                                border           : none;
                            }
                        }

                        i.separator {
                            opacity : 0;
                        }
                    }

                    .CodeMirror {
                        border  : none;
                        padding : 0;
                    }

                    .CodeMirror-scroll {
                        overflow       : auto !important;
                        min-height     : 300px;
                        max-height     : 300px;
                        font-size      : 1rem;
                        padding-bottom : 0;
                        font-weight    : 300;
                    }

                    .editor-preview.editor-preview-active {
                        font-size   : 1rem;
                        font-weight : 300;
                        padding     : 0 .5rem;

                        p {
                            margin-bottom : 1em;
                        }

                        em {
                            font-style : italic;
                        }

                        strong {
                            font-weight : bold;
                        }

                        h1 {
                            font-size   : 200%;
                            line-height : 200%;
                        }
                    }

                    .warning {
                        margin : 0 0 4px;
                    }
                }

                .foldout-container {
                    margin-bottom : 1.25rem;

                    &.first-open {
                        transition : none;
                    }

                    .foldout-title {
                        font-weight : 300;
                        font-size   : 1.25rem
                    }
                }

            }

            .controls {
                grid-area  : controls;
                align-self : end;

                input {
                    width     : 100%;
                    font-size : 1.1rem;
                }
            }
        }
    }

</style>