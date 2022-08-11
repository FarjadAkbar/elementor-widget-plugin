window.addEventListener('elementor/init', () => {

    // const currentPageURL = elementor.documents.currentDocument.config.urls.permalink;

    // const pageSpeedURL = `https://developers.google.com/speed/pagespeed/insights/?url=${currentPageURL}&tab=desktop`;

    function a(b) {
        return b.forEach(function (b) {
            b.id = elementorCommon.helpers.getUniqueId(), 0 < b.elements.length && a(b.elements)
        }), b
    }

    function StoreCP(localST, callback) {
        callback(localST, callback);
    }

    // localStorage.init({
    //     iframeUrl: "https://leap13.github.io/pa-cdcp/",
    //     initCallback: function () { }
    // });


    CrossCopyPasteHandler = {
        b: function (b, c) {
            var d = c,
                e = c.model.get("elType"),
                f = b.elecode.elType,
                g = b.elecode,
                h = JSON.stringify(g);

            var i = /\.(jpg|png|jpeg|gif|svg)/gi.test(h),
                j = {
                    elType: f,
                    settings: g.settings
                },
                k = null,
                l = {
                    index: 0
                };

            switch (f) {
                case "section":
                case "container":
                    j.elements = a(g.elements), k = elementor.getPreviewContainer();
                    break;
                case "column":
                    j.elements = a(g.elements);
                    ("section" === e || "container" === e) ? k = d.getContainer() : "column" === e ? (k = d.getContainer().parent, l.index = d.getOption("_index") + 1) : "widget" === e ? (k = d.getContainer().parent.parent, l.index = d.getContainer().parent.view.getOption("_index") + 1) : void 0;
                    break;
                case "widget":
                    j.widgetType = b.eletype, k = d.getContainer();
                    ("section" === e) ? k = d.children.findByIndex(0).getContainer() : "column" === e ? k = d.getContainer() : "widget" === e ? (k = d.getContainer().parent, e.index = d.getOption("_index") + 1, l.index = d.getOption("_index") + 1) : void 0;
            }
            var m = $e.run("document/elements/create", {
                model: j,
                container: k,
                options: l
            });
            i && jQuery.ajax({
                url: fa_cross_cp.ajax_url,
                method: "POST",
                data: {
                    nonce: fa_cross_cp.nonce,
                    action: "fa_cross_cp_import",
                    copy_content: h
                }
            }).done(function (a) {
                if (a.success) {
                    var b = a.data[0];
                    j.elType = b.elType, j.settings = b.settings, "widget" === j.elType ? j.widgetType = b.widgetType : j.elements = b.elements, $e.run("document/elements/delete", {
                        container: m
                    }), $e.run("document/elements/create", {
                        model: j,
                        container: k,
                        options: l
                    })
                }
            })
        },


        pasteAll: function (allSections) {
            jQuery.ajax({
                url: fa_cross_cp.ajax_url,
                method: "POST",
                data: {
                    nonce: fa_cross_cp.nonce,
                    action: "fa_cross_cp_import",
                    copy_content: allSections
                },
            }).done(function (e) {
                if (e.success) {
                    var data = e.data[0];
                    if (fa_cross_cp.elementorCompatible) {
                        elementor.sections.currentView.addChildModel(data)
                    } else {
                        elementor.previewView.addChildModel(data)
                    }
                    elementor.notifications.showToast({
                        message: elementor.translate('Content Pasted. Have Fun ;)')
                    });

                }
            }).fail(function () {
                elementor.notifications.showToast({
                    message: elementor.translate('Something went wrong!')
                });
            })
        }
    }

    const elTypes = ['widget', 'column', 'section'];
    d = [];
    // Google PageSpeed action object
    elTypes.forEach((a, e) => {
        elementor.hooks.addFilter("elements/" + elTypes[e] + "/contextMenuGroups", function (a, f) {
            return d.push(f), a.push({
                name: "fa_" + elTypes[e],
                actions: [{
                    name: 'fa-spot-copy',
                    icon: 'eicon-copy',
                    title: 'FA | Copy Element',
                    isEnabled: () => true,
                    callback: () => {
                        var a = {};

                        a.eletype = "widget" == elTypes[e] ? f.model.get("widgetType") : null;
                        a.elecode = f.model.toJSON();

                        StoreCP(
                            localStorage.setItem("fa-cp-element", JSON.stringify(a)),
                            () => {
                                elementor.notifications.showToast({
                                    message: elementor.translate('Copied')
                                });
                            }
                        );
                        // localStorage.setItem("fa-cp-element", JSON.stringify(a));
                    },
                },
                {
                    name: 'fa-spot-paste',
                    icon: 'eicon-pate',
                    title: 'FA | Paste Element',
                    isEnabled: () => true,
                    callback: () => {
                        StoreCP(
                            a = localStorage.getItem("fa-cp-element"),
                            () => {
                                CrossCopyPasteHandler.b(JSON.parse(a.value), f)
                            }
                        );
                        // localStorage.getItem("fa-cp-element", () => {
                        //     CrossCopyPasteHandler.b(JSON.parse(a.value), f)
                        // });
                    },
                },

                ]
            }), a
        });
    })
    // const newAction = {
    //     name: 'fa-spot-copy',
    //     icon: 'eicon-copy',
    //     title: 'FA | Copy Element',
    //     isEnabled: () => true,
    //     callback: () => {
    //         var a = {};

    //         a.eletype = "widget" == c[e] ? f.model.get("widgetType") : null;
    //         a.elecode = f.model.toJSON();
    //         console.log(a);
    //     },
    // };

    // Add "Google PageSpeed" action to widget/column/section context menus.
    // elTypes.forEach((elType) => {

    //     elementor.hooks.addFilter(`elements/${elType}/contextMenuGroups`, (groups, view) => {

    //         groups.forEach((group) => {
    //             if ('general' === group.name) {
    //                 group.actions.push(newAction);
    //             }
    //         });

    //         return groups;

    //     });

    // });

});
