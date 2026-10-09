(function (wp) {
    'use strict';

    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components) {
        return;
    }

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useEffect = wp.element.useEffect;
    var useMemo = wp.element.useMemo;
    var useState = wp.element.useState;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockControls = wp.blockEditor.BlockControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var Placeholder = wp.components.Placeholder;
    var Notice = wp.components.Notice;
    var Spinner = wp.components.Spinner;
    var Button = wp.components.Button;
    var Disabled = wp.components.Disabled;
    var ToolbarGroup = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;
    var useSelect = wp.data.useSelect;
    var apiFetch = wp.apiFetch;
    var addQueryArgs = wp.url && wp.url.addQueryArgs;
    var __ = wp.i18n.__;

    var serverSideRenderPackage = wp.serverSideRender;
    var ServerSideRender = serverSideRenderPackage && (
        serverSideRenderPackage.ServerSideRender ||
        serverSideRenderPackage.default ||
        serverSideRenderPackage
    );
    var fieldRequestCache = {};
    var fieldBlockSettings = window.wpbbAcfFieldSettings || {};

    function fetchFieldDefinitions(path) {
        if (!fieldRequestCache[path]) {
            fieldRequestCache[path] = apiFetch({ path: path }).catch(function (error) {
                delete fieldRequestCache[path];
                throw error;
            });
        }

        return fieldRequestCache[path];
    }

    var SOURCE_OPTIONS = [
        {
            label: __('Current post / Query Loop item', 'wp-bbuilder'),
            value: 'current_post'
        }
    ];

    if (fieldBlockSettings.allowOptions !== false) {
        SOURCE_OPTIONS.push({
            label: __('Options', 'wp-bbuilder'),
            value: 'option'
        });
    }

    SOURCE_OPTIONS.push(
        {
            label: __('Current term', 'wp-bbuilder'),
            value: 'current_term'
        },
        {
            label: __('Current user / author', 'wp-bbuilder'),
            value: 'current_user'
        }
    );

    var DISPLAY_LABELS = {
        auto: __('Automatic', 'wp-bbuilder'),
        text: __('Text', 'wp-bbuilder'),
        image: __('Image', 'wp-bbuilder'),
        button: __('Button / link', 'wp-bbuilder'),
        embed: __('Embed', 'wp-bbuilder'),
        icon: __('Icon', 'wp-bbuilder')
    };

    var TAG_OPTIONS = [
        { label: '<p>', value: 'p' },
        { label: '<div>', value: 'div' },
        { label: '<span>', value: 'span' },
        { label: '<h1>', value: 'h1' },
        { label: '<h2>', value: 'h2' },
        { label: '<h3>', value: 'h3' },
        { label: '<h4>', value: 'h4' },
        { label: '<h5>', value: 'h5' },
        { label: '<h6>', value: 'h6' },
        { label: '<ul>', value: 'ul' },
        { label: '<ol>', value: 'ol' }
    ];

    var IMAGE_SIZE_OPTIONS = [
        { label: __('Thumbnail', 'wp-bbuilder'), value: 'thumbnail' },
        { label: __('Medium', 'wp-bbuilder'), value: 'medium' },
        { label: __('Medium large', 'wp-bbuilder'), value: 'medium_large' },
        { label: __('Large', 'wp-bbuilder'), value: 'large' },
        { label: __('Full size', 'wp-bbuilder'), value: 'full' }
    ];

    function positiveInteger(value) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    }

    function matchKnownObjectSlug(templateSuffix, objects) {
        var slugs = (objects || []).map(function (object) {
            return object && (object.slug || object.name) ? (object.slug || object.name) : '';
        }).filter(Boolean).sort(function (left, right) {
            return right.length - left.length;
        });

        for (var index = 0; index < slugs.length; index += 1) {
            if (templateSuffix === slugs[index] || templateSuffix.indexOf(slugs[index] + '-') === 0) {
                return slugs[index];
            }
        }

        return '';
    }

    function inferTemplateTarget(editedEntityId, postType, taxonomy, postTypes, taxonomies) {
        var result = {
            postType: postType || '',
            taxonomy: taxonomy || ''
        };

        if (typeof editedEntityId !== 'string' || editedEntityId.indexOf('//') === -1) {
            return result;
        }

        var slug = editedEntityId.split('//').pop() || '';

        if (!result.postType || result.postType === 'wp_template' || result.postType === 'wp_template_part') {
            if (slug === 'single') {
                result.postType = 'post';
            } else if (slug === 'page' || slug.indexOf('page-') === 0) {
                result.postType = 'page';
            } else if (slug.indexOf('single-') === 0) {
                var singleSuffix = slug.replace(/^single-/, '');
                result.postType = matchKnownObjectSlug(singleSuffix, postTypes) || singleSuffix;
            } else {
                result.postType = '';
            }
        }

        if (!result.taxonomy) {
            if (slug === 'category' || slug.indexOf('category-') === 0) {
                result.taxonomy = 'category';
            } else if (slug === 'tag' || slug.indexOf('tag-') === 0) {
                result.taxonomy = 'post_tag';
            } else if (slug.indexOf('taxonomy-') === 0) {
                var taxonomySuffix = slug.replace(/^taxonomy-/, '');
                result.taxonomy = matchKnownObjectSlug(taxonomySuffix, taxonomies) || taxonomySuffix;
            }
        }

        return result;
    }

    function getDisplayModes(fieldType, multiple, serverModes) {
        if (Array.isArray(serverModes) && serverModes.length) {
            return serverModes;
        }

        var type = fieldType || '';
        var modes = ['auto'];

        if (type === 'image') {
            return modes.concat(['image', 'text']);
        }

        if (type === 'oembed') {
            return modes.concat(['embed', 'text']);
        }

        if (type === 'icon_picker') {
            return modes.concat(['icon', 'text']);
        }

        if (type === 'url') {
            return modes.concat(['text', 'button', 'embed']);
        }

        if (['email', 'file', 'link', 'page_link'].indexOf(type) !== -1) {
            return modes.concat(['text', 'button']);
        }

        if (['post_object', 'relationship', 'taxonomy', 'user'].indexOf(type) !== -1) {
            modes.push('text');
            if (!multiple) {
                modes.push('button');
            }
            return modes;
        }

        if (type) {
            modes.push('text');
        }

        return modes;
    }

    function flattenFieldOptions(groups, selectedKey, selectedLabel) {
        var options = [
            {
                label: __('Select an ACF field', 'wp-bbuilder'),
                value: ''
            }
        ];
        var hasSelected = false;

        (groups || []).forEach(function (group) {
            (group.fields || []).forEach(function (field) {
                var suffix = field.supported ? ' (' + field.type + ')' : ' (' + field.type + ' - ' + __('not supported', 'wp-bbuilder') + ')';
                options.push({
                    label: group.title + ' — ' + field.label + suffix,
                    value: field.key,
                    disabled: !field.supported
                });
                if (field.key === selectedKey) {
                    hasSelected = true;
                }
            });
        });

        if (selectedKey && !hasSelected) {
            options.push({
                label: (selectedLabel || selectedKey) + ' (' + __('previously selected', 'wp-bbuilder') + ')',
                value: selectedKey
            });
        }

        return options;
    }

    function findField(groups, key) {
        var found = null;
        (groups || []).some(function (group) {
            return (group.fields || []).some(function (field) {
                if (field.key === key) {
                    found = field;
                    return true;
                }
                return false;
            });
        });
        return found;
    }

    function FieldControls(props) {
        var attributes = props.attributes;
        var data = props.data;
        var fieldOptions = props.fieldOptions;
        var displayOptions = props.displayOptions;
        var onSourceChange = props.onSourceChange;
        var onFieldChange = props.onFieldChange;
        var setAttributes = props.setAttributes;
        var loading = props.loading;
        var compact = props.compact;

        return el(
            Fragment,
            null,
            el(SelectControl, {
                label: __('Field source', 'wp-bbuilder'),
                value: attributes.fieldSource || 'current_post',
                options: SOURCE_OPTIONS,
                onChange: onSourceChange,
                disabled: loading,
                __nextHasNoMarginBottom: true
            }),
            el(SelectControl, {
                label: __('ACF field', 'wp-bbuilder'),
                value: attributes.fieldKey || '',
                options: fieldOptions,
                onChange: onFieldChange,
                disabled: loading || !data.available || data.restricted,
                help: data.message || null,
                __nextHasNoMarginBottom: true
            }),
            attributes.fieldKey && displayOptions.length > 1 ? el(SelectControl, {
                label: __('Display as', 'wp-bbuilder'),
                value: attributes.displayAs || 'auto',
                options: displayOptions,
                onChange: function (value) {
                    setAttributes({ displayAs: value });
                },
                __nextHasNoMarginBottom: true
            }) : null,
            !compact && loading ? el('div', { className: 'wpbb-acf-field-editor__status' }, el(Spinner), ' ', __('Loading fields…', 'wp-bbuilder')) : null
        );
    }

    function Edit(props) {
        var attributes = props.attributes;
        var setAttributes = props.setAttributes;
        var context = props.context || {};
        var clientId = props.clientId;
        var blockProps = useBlockProps({ className: 'wpbb-acf-field-editor' });

        var editorContext = useSelect(function (select) {
            var editor = select('core/editor');
            var siteEditor = select('core/edit-site');
            var core = select('core');
            var postId = editor && editor.getCurrentPostId ? editor.getCurrentPostId() : 0;
            var postType = editor && editor.getCurrentPostType ? editor.getCurrentPostType() : '';
            var editedEntityId = siteEditor && siteEditor.getEditedPostId ? siteEditor.getEditedPostId() : '';
            var isSaving = editor && editor.isSavingPost ? editor.isSavingPost() : false;
            var didSave = editor && editor.didPostSaveRequestSucceed ? editor.didPostSaveRequestSucceed() : false;
            var postTypes = core && core.getPostTypes ? core.getPostTypes() : [];
            var taxonomies = core && core.getTaxonomies ? core.getTaxonomies() : [];

            return {
                postId: postId,
                postType: postType,
                editedEntityId: editedEntityId,
                isSaving: isSaving,
                didSave: didSave,
                postTypes: postTypes || [],
                taxonomies: taxonomies || []
            };
        }, []);

        var templateTarget = inferTemplateTarget(
            editorContext.editedEntityId,
            context.postType || editorContext.postType,
            context.taxonomy || '',
            editorContext.postTypes,
            editorContext.taxonomies
        );
        var contextualPostId = positiveInteger(context.postId) || positiveInteger(editorContext.postId);
        var contextualTermId = positiveInteger(context.termId);
        var contextualUserId = positiveInteger(context.userId);
        var contextualPostType = templateTarget.postType || '';
        var contextualTaxonomy = templateTarget.taxonomy || '';

        var dataState = useState({
            available: true,
            restricted: false,
            message: '',
            groups: []
        });
        var data = dataState[0];
        var setData = dataState[1];
        var loadingState = useState(true);
        var loading = loadingState[0];
        var setLoading = loadingState[1];
        var errorState = useState('');
        var error = errorState[0];
        var setError = errorState[1];
        var pickerState = useState(!attributes.fieldKey);
        var showPicker = pickerState[0];
        var setShowPicker = pickerState[1];
        var previewVersionState = useState(0);
        var previewVersion = previewVersionState[0];
        var setPreviewVersion = previewVersionState[1];
        var wasSavingState = useState(false);
        var wasSaving = wasSavingState[0];
        var setWasSaving = wasSavingState[1];

        useEffect(function () {
            var active = true;
            var query = {
                source: attributes.fieldSource || 'current_post',
                post_id: contextualPostId || undefined,
                post_type: contextualPostType || undefined,
                taxonomy: contextualTaxonomy || undefined,
                term_id: contextualTermId || undefined,
                user_id: contextualUserId || undefined
            };
            var path = addQueryArgs ? addQueryArgs('/wp-bbuilder/v1/acf-fields', query) : '/wp-bbuilder/v1/acf-fields';

            setLoading(true);
            setError('');

            fetchFieldDefinitions(path).then(function (response) {
                if (!active) {
                    return;
                }
                setData(response || { available: false, groups: [] });
                setLoading(false);
            }).catch(function (requestError) {
                if (!active) {
                    return;
                }
                setError(requestError && requestError.message ? requestError.message : __('Unable to load ACF fields.', 'wp-bbuilder'));
                setLoading(false);
            });

            return function () {
                active = false;
            };
        }, [
            attributes.fieldSource,
            contextualPostId,
            contextualPostType,
            contextualTaxonomy,
            contextualTermId,
            contextualUserId
        ]);

        useEffect(function () {
            if (editorContext.isSaving) {
                if (!wasSaving) {
                    setWasSaving(true);
                }
                return;
            }

            if (wasSaving && editorContext.didSave) {
                setWasSaving(false);
                setPreviewVersion(function (value) {
                    return value + 1;
                });
            }
        }, [editorContext.isSaving, editorContext.didSave, wasSaving]);

        var selectedField = useMemo(function () {
            return findField(data.groups, attributes.fieldKey);
        }, [data.groups, attributes.fieldKey]);

        useEffect(function () {
            if (!selectedField) {
                return;
            }

            var updates = {};
            var validModes = getDisplayModes(selectedField.type, selectedField.multiple, selectedField.displayModes);

            if ((attributes.fieldName || '') !== (selectedField.name || '')) {
                updates.fieldName = selectedField.name || '';
            }
            if ((attributes.fieldLabel || '') !== (selectedField.label || '')) {
                updates.fieldLabel = selectedField.label || '';
            }
            if ((attributes.fieldType || '') !== (selectedField.type || '')) {
                updates.fieldType = selectedField.type || '';
            }
            if (!!attributes.fieldMultiple !== !!selectedField.multiple) {
                updates.fieldMultiple = !!selectedField.multiple;
            }
            if (validModes.indexOf(attributes.displayAs || 'auto') === -1) {
                updates.displayAs = 'auto';
            }

            if (Object.keys(updates).length) {
                setAttributes(updates);
            }
        }, [
            selectedField,
            attributes.fieldName,
            attributes.fieldLabel,
            attributes.fieldType,
            attributes.fieldMultiple,
            attributes.displayAs
        ]);

        var fieldOptions = useMemo(function () {
            return flattenFieldOptions(data.groups, attributes.fieldKey, attributes.fieldLabel);
        }, [data.groups, attributes.fieldKey, attributes.fieldLabel]);

        var displayModes = getDisplayModes(
            selectedField ? selectedField.type : attributes.fieldType,
            selectedField ? selectedField.multiple : attributes.fieldMultiple,
            selectedField ? selectedField.displayModes : null
        );
        var displayOptions = displayModes.map(function (mode) {
            return {
                label: DISPLAY_LABELS[mode] || mode,
                value: mode
            };
        });

        function onSourceChange(value) {
            setAttributes({
                fieldSource: value,
                fieldKey: '',
                fieldName: '',
                fieldLabel: '',
                fieldType: '',
                fieldMultiple: false,
                displayAs: 'auto'
            });
            setShowPicker(true);
        }

        function onFieldChange(value) {
            var field = findField(data.groups, value);

            if (!value) {
                setAttributes({
                    fieldKey: '',
                    fieldName: '',
                    fieldLabel: '',
                    fieldType: '',
                    fieldMultiple: false,
                    displayAs: 'auto'
                });
                return;
            }

            if (!field) {
                setAttributes({ fieldKey: value });
                return;
            }

            setAttributes({
                fieldKey: field.key,
                fieldName: field.name || '',
                fieldLabel: field.label || '',
                fieldType: field.type || '',
                fieldMultiple: !!field.multiple,
                displayAs: 'auto'
            });
            setShowPicker(false);
        }

        var inspector = el(
            InspectorControls,
            null,
            el(
                PanelBody,
                {
                    title: __('ACF field', 'wp-bbuilder'),
                    initialOpen: true,
                    className: 'wpbb-inspector-panel'
                },
                el(FieldControls, {
                    attributes: attributes,
                    data: data,
                    fieldOptions: fieldOptions,
                    displayOptions: displayOptions,
                    onSourceChange: onSourceChange,
                    onFieldChange: onFieldChange,
                    setAttributes: setAttributes,
                    loading: loading,
                    compact: false
                })
            ),
            attributes.fieldKey ? el(
                PanelBody,
                {
                    title: __('Display settings', 'wp-bbuilder'),
                    initialOpen: true,
                    className: 'wpbb-inspector-panel'
                },
                (attributes.displayAs === 'auto' || attributes.displayAs === 'text') ? el(
                    Fragment,
                    null,
                    el(SelectControl, {
                        label: __('HTML element', 'wp-bbuilder'),
                        value: attributes.tagName || 'p',
                        options: TAG_OPTIONS,
                        onChange: function (value) {
                            setAttributes({ tagName: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    el(TextControl, {
                        label: __('Prefix', 'wp-bbuilder'),
                        value: attributes.prefix || '',
                        onChange: function (value) {
                            setAttributes({ prefix: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    el(TextControl, {
                        label: __('Suffix', 'wp-bbuilder'),
                        value: attributes.suffix || '',
                        onChange: function (value) {
                            setAttributes({ suffix: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    attributes.fieldMultiple ? el(TextControl, {
                        label: __('Multiple-value separator', 'wp-bbuilder'),
                        value: attributes.separator || ', ',
                        onChange: function (value) {
                            setAttributes({ separator: value });
                        },
                        __nextHasNoMarginBottom: true
                    }) : null,
                    attributes.fieldType === 'true_false' ? el(
                        Fragment,
                        null,
                        el(TextControl, {
                            label: __('True value text', 'wp-bbuilder'),
                            value: attributes.trueText || 'Yes',
                            onChange: function (value) {
                                setAttributes({ trueText: value });
                            },
                            __nextHasNoMarginBottom: true
                        }),
                        el(TextControl, {
                            label: __('False value text', 'wp-bbuilder'),
                            value: attributes.falseText || 'No',
                            onChange: function (value) {
                                setAttributes({ falseText: value });
                            },
                            __nextHasNoMarginBottom: true
                        })
                    ) : null
                ) : null,
                attributes.displayAs === 'button' ? el(
                    Fragment,
                    null,
                    el(TextControl, {
                        label: __('Button label override', 'wp-bbuilder'),
                        value: attributes.buttonLabel || '',
                        onChange: function (value) {
                            setAttributes({ buttonLabel: value });
                        },
                        help: __('Leave empty to use the field value or field label.', 'wp-bbuilder'),
                        __nextHasNoMarginBottom: true
                    }),
                    el(ToggleControl, {
                        label: __('Open in a new tab', 'wp-bbuilder'),
                        checked: !!attributes.openInNewTab,
                        onChange: function (value) {
                            setAttributes({ openInNewTab: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    el(ToggleControl, {
                        label: __('Mark link as nofollow', 'wp-bbuilder'),
                        checked: !!attributes.nofollow,
                        onChange: function (value) {
                            setAttributes({ nofollow: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    attributes.fieldType === 'file' ? el(ToggleControl, {
                        label: __('Download file instead of opening it', 'wp-bbuilder'),
                        checked: !!attributes.downloadFile,
                        onChange: function (value) {
                            setAttributes({ downloadFile: value });
                        },
                        __nextHasNoMarginBottom: true
                    }) : null
                ) : null,
                attributes.displayAs === 'image' || (attributes.displayAs === 'auto' && attributes.fieldType === 'image') ? el(
                    Fragment,
                    null,
                    el(SelectControl, {
                        label: __('Image size', 'wp-bbuilder'),
                        value: attributes.imageSize || 'large',
                        options: IMAGE_SIZE_OPTIONS,
                        onChange: function (value) {
                            setAttributes({ imageSize: value });
                        },
                        __nextHasNoMarginBottom: true
                    }),
                    el(ToggleControl, {
                        label: __('Show image caption', 'wp-bbuilder'),
                        checked: !!attributes.showImageCaption,
                        onChange: function (value) {
                            setAttributes({ showImageCaption: value });
                        },
                        __nextHasNoMarginBottom: true
                    })
                ) : null,
                attributes.displayAs === 'icon' || (attributes.displayAs === 'auto' && attributes.fieldType === 'icon_picker') ? el(RangeControl, {
                    label: __('Icon size', 'wp-bbuilder'),
                    value: attributes.iconSize || 32,
                    min: 12,
                    max: 160,
                    onChange: function (value) {
                        setAttributes({ iconSize: value });
                    },
                    __nextHasNoMarginBottom: true
                }) : null,
                el(ToggleControl, {
                    label: __('Show a message when the value is empty', 'wp-bbuilder'),
                    checked: !!attributes.showEmptyMessage,
                    onChange: function (value) {
                        setAttributes({ showEmptyMessage: value });
                    },
                    __nextHasNoMarginBottom: true
                }),
                attributes.showEmptyMessage ? el(TextControl, {
                    label: __('Empty value message', 'wp-bbuilder'),
                    value: attributes.emptyMessage || '',
                    onChange: function (value) {
                        setAttributes({ emptyMessage: value });
                    },
                    __nextHasNoMarginBottom: true
                }) : null
            ) : null
        );

        var toolbar = attributes.fieldKey ? el(
            BlockControls,
            null,
            el(
                ToolbarGroup,
                null,
                el(ToolbarButton, {
                    icon: 'update',
                    label: __('Replace ACF field', 'wp-bbuilder'),
                    onClick: function () {
                        setShowPicker(!showPicker);
                    }
                })
            )
        ) : null;

        var content;

        if (error) {
            content = el(
                'div',
                blockProps,
                el(Notice, { status: 'error', isDismissible: false }, error)
            );
        } else if (!data.available) {
            content = el(
                'div',
                blockProps,
                el(Placeholder, {
                    icon: 'database-view',
                    label: __('ACF Field', 'wp-bbuilder'),
                    instructions: data.message || __('Advanced Custom Fields 6.1 or newer must be active.', 'wp-bbuilder'),
                    className: 'wpbb-acf-field-editor__placeholder'
                })
            );
        } else if (!attributes.fieldKey || showPicker) {
            content = el(
                'div',
                blockProps,
                el(
                    Placeholder,
                    {
                        icon: 'database-view',
                        label: attributes.fieldKey ? __('Replace ACF field', 'wp-bbuilder') : __('Choose an ACF field', 'wp-bbuilder'),
                        instructions: __('Select a source and field. The block reads saved ACF values and does not edit them.', 'wp-bbuilder'),
                        className: 'wpbb-acf-field-editor__placeholder'
                    },
                    el(FieldControls, {
                        attributes: attributes,
                        data: data,
                        fieldOptions: fieldOptions,
                        displayOptions: displayOptions,
                        onSourceChange: onSourceChange,
                        onFieldChange: onFieldChange,
                        setAttributes: setAttributes,
                        loading: loading,
                        compact: true
                    }),
                    attributes.fieldKey ? el(Button, {
                        variant: 'secondary',
                        onClick: function () {
                            setShowPicker(false);
                        }
                    }, __('Cancel', 'wp-bbuilder')) : null,
                    loading ? el('div', { className: 'wpbb-acf-field-editor__status' }, el(Spinner), ' ', __('Loading fields…', 'wp-bbuilder')) : null
                )
            );
        } else {
            var urlQueryArgs = contextualPostId ? { post_id: contextualPostId } : {};
            var preview = ServerSideRender ? el(ServerSideRender, {
                key: clientId + '-' + contextualPostId + '-' + previewVersion,
                block: 'wpbb/acf-field',
                attributes: attributes,
                urlQueryArgs: urlQueryArgs,
                EmptyResponsePlaceholder: function () {
                    return el(Notice, { status: 'info', isDismissible: false }, __('The selected field has no saved value in this context.', 'wp-bbuilder'));
                },
                ErrorResponsePlaceholder: function (previewError) {
                    var message = previewError && previewError.message ? previewError.message : __('The field preview could not be rendered.', 'wp-bbuilder');
                    return el(Notice, { status: 'error', isDismissible: false }, message);
                },
                LoadingResponsePlaceholder: function () {
                    return el('div', { className: 'wpbb-acf-field-editor__status' }, el(Spinner), ' ', __('Loading preview…', 'wp-bbuilder'));
                }
            }) : el(Notice, { status: 'warning', isDismissible: false }, __('Server-side block previews are unavailable in this WordPress version.', 'wp-bbuilder'));

            content = el(
                'div',
                blockProps,
                el(
                    'div',
                    { className: 'wpbb-acf-field-editor__header' },
                    el(
                        'div',
                        { className: 'wpbb-acf-field-editor__field' },
                        el('strong', null, attributes.fieldLabel || attributes.fieldName || attributes.fieldKey),
                        el('span', null, (attributes.fieldType || __('ACF field', 'wp-bbuilder')) + ' · ' + (DISPLAY_LABELS[attributes.displayAs || 'auto'] || attributes.displayAs))
                    ),
                    el(Button, {
                        variant: 'tertiary',
                        onClick: function () {
                            setShowPicker(true);
                        }
                    }, __('Replace', 'wp-bbuilder'))
                ),
                el('div', { className: 'wpbb-acf-field-editor__preview' }, el(Disabled, null, preview))
            );
        }

        return el(Fragment, null, inspector, toolbar, content);
    }

    registerBlockType('wpbb/acf-field', {
        apiVersion: 3,
        title: __('ACF Field', 'wp-bbuilder'),
        description: __('Display a saved Advanced Custom Fields value.', 'wp-bbuilder'),
        category: 'wpbb',
        icon: 'database-view',
        keywords: [__('acf', 'wp-bbuilder'), __('custom field', 'wp-bbuilder'), __('dynamic', 'wp-bbuilder')],
        usesContext: [
            'postId',
            'postType',
            'queryId',
            'termId',
            'taxonomy',
            'userId'
        ],
        attributes: {
            fieldKey: { type: 'string', default: '' },
            fieldName: { type: 'string', default: '' },
            fieldLabel: { type: 'string', default: '' },
            fieldType: { type: 'string', default: '' },
            fieldMultiple: { type: 'boolean', default: false },
            fieldSource: { type: 'string', default: 'current_post' },
            displayAs: { type: 'string', default: 'auto' },
            tagName: { type: 'string', default: 'p' },
            prefix: { type: 'string', default: '' },
            suffix: { type: 'string', default: '' },
            separator: { type: 'string', default: ', ' },
            showEmptyMessage: { type: 'boolean', default: false },
            emptyMessage: { type: 'string', default: '' },
            trueText: { type: 'string', default: 'Yes' },
            falseText: { type: 'string', default: 'No' },
            buttonLabel: { type: 'string', default: '' },
            openInNewTab: { type: 'boolean', default: false },
            nofollow: { type: 'boolean', default: false },
            downloadFile: { type: 'boolean', default: false },
            imageSize: { type: 'string', default: 'large' },
            showImageCaption: { type: 'boolean', default: false },
            iconSize: { type: 'number', default: 32 }
        },
        supports: {
            anchor: true,
            align: ['wide', 'full'],
            html: false,
            inserter: fieldBlockSettings.inserterEnabled !== false,
            color: {
                text: true,
                background: true,
                link: true,
                gradients: true
            },
            spacing: {
                margin: true,
                padding: true
            },
            typography: {
                fontSize: true,
                lineHeight: true,
                __experimentalFontFamily: true,
                __experimentalFontWeight: true,
                __experimentalFontStyle: true,
                __experimentalTextTransform: true,
                __experimentalTextDecoration: true,
                __experimentalLetterSpacing: true
            }
        },
        edit: Edit,
        save: function () {
            return null;
        },
        __experimentalLabel: function (attributes) {
            return attributes.fieldLabel ? __('ACF:', 'wp-bbuilder') + ' ' + attributes.fieldLabel : __('ACF Field', 'wp-bbuilder');
        }
    });
})(window.wp);
