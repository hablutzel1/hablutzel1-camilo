

    var dialog = window.parent;
    var editorWindow = dialog.InnerDialogLoaded();
    var editorInstance = editorWindow.FCK;
    var FCKConfig = editorWindow.FCKConfig;
    var FCKTools = editorWindow.FCKTools;
    var FCKBrowserInfo = editorWindow.FCKBrowserInfo;


    // onload
    window.onload = function()
    {
        var description, snippet;

        // show snippets to choose from
        if (typeof(FCKConfig.insertHtml_snippets) == 'object')
        {
            var snippetsDiv, snippetDiv, numberOfSnippets = 0;

            snippetsDiv = document.createElement('div');
            snippetsDiv.id = 'snippets';

            for (description in FCKConfig.insertHtml_snippets)
            {
                snippetDiv = document.createElement('div');
                snippetDiv.innerHTML = description;
                snippetDiv.className = 'snippet';
                snippetDiv.snippet = FCKConfig.insertHtml_snippets[description];
                snippetDiv.onmouseover = function(){this.className += ' PopupSelectionBox'};
                snippetDiv.onmouseout = function(){this.className = this.className.replace(/\s?PopupSelectionBox\s?/, '')};
                if (FCKConfig.insertHtml_showTextarea)
                {
                    snippetDiv.onclick = function(){
                        document.getElementById('insertHtmlTextArea').value = this.snippet;
                    };
                }
                else
                {
                    snippetDiv.onclick = function(){
                        editorInstance.InsertHtml(this.snippet);
                        editorWindow.FCKUndo.SaveUndoStep();
                        dialog.CloseDialog();
                    };
                }
                snippetsDiv.appendChild(snippetDiv);

                numberOfSnippets++;
            }
            document.getElementById('content').appendChild(snippetsDiv);

            // load dialog
        }

        // show textarea
        if (FCKConfig.insertHtml_showTextarea || !FCKConfig.insertHtml_snippets || !numberOfSnippets)
        {
            insertHtmlTextArea = document.createElement('textarea');
            insertHtmlTextArea.id = 'insertHtmlTextArea';
            document.getElementById('content').appendChild(insertHtmlTextArea);
            // set the size of the textarea
            insertHtmlTextArea.style.width = (FCKConfig.insertHtml_textareaWidth || 300) + 'px';
            insertHtmlTextArea.style.height = (FCKConfig.insertHtml_textareaHeight || 100) + 'px';
            // load default content
            if (typeof(FCKConfig.insertHtml_snippets) == 'object')
            {
                for (description in FCKConfig.insertHtml_snippets)
                {
                    snippet = FCKConfig.insertHtml_snippets[description];
                    break;
                }
            }
            else
            {
                //snippet = FCKConfig.insertHtml_snippets;//Chamilo replaced by below (by now)
                snippet = "";//Insert your tex here
            }
            insertHtmlTextArea.value = snippet;
        }

        // resize around snippets and/or textarea
        // for IE this must be done before translating the dialog or the dialog will be to wide; also IE needs an approximate resize before autofitting or the dialog width will be to large
        if (FCKBrowserInfo.IsIE) dialog.Sizer.ResizeDialog(parseInt(FCKConfig.insertHtml_textareaWidth || 300), parseInt(FCKConfig.insertHtml_textareaHeight || 100) + 130);
        dialog.SetAutoSize(true);

        // recenter dialog
        setTimeout(function(){ // after a dummy delay, needed for webkit
            var topWindowSize = FCKTools.GetViewPaneSize(dialog.top.window);
            dialog.frameElement.style.left = Math.round((topWindowSize.Width - dialog.frameElement.offsetWidth) / 2) + 'px';
            dialog.frameElement.style.top = Math.round((topWindowSize.Height - dialog.frameElement.offsetHeight) / 2).toString() + 'px';;
        }, 0);

        // translate the dialog box texts
        editorWindow.FCKLanguageManager.TranslatePage(document);

        // activate the "OK" button
        dialog.SetOkButton(true);
    }

    // dialog's 'ok' button function to insert the Html
    function Ok()
    {
        if (insertHtmlTextArea.value)
        {
            editorInstance.InsertHtml(insertHtmlTextArea.value);
            editorWindow.FCKUndo.SaveUndoStep();

            return true; // makes the dialog to close
        }
    }

