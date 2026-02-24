/**
 * Inform JavaScript埋め込みライブラリ
 * 
 * 任意のウェブサイトにお問い合わせフォームを埋め込むための
 * JavaScriptライブラリです。
 * 
 * 使用方法:
 * <div id="inform-form-container"></div>
 * <script src="https://example.com/public/inform/embed.js"></script>
 * <script>
 *   InformEmbed.render('inform-form-container', 1);
 * </script>
 * 
 * 要件: 4.2
 */

(function(window) {
    'use strict';

    /**
     * InformEmbedオブジェクト
     */
    const InformEmbed = {
        /**
         * 埋め込みフォームのベースURL
         * 本番環境では適切なURLに変更してください
         */
        baseUrl: window.INFORM_BASE_URL || '',

        /**
         * フォームをレンダリング
         * 
         * @param {string} elementId - フォームを注入する要素のID
         * @param {number} formId - フォームID
         * @param {object} options - オプション設定
         */
        render: function(elementId, formId, options) {
            options = options || {};

            // ターゲット要素を取得
            const targetElement = document.getElementById(elementId);
            if (!targetElement) {
                console.error('Inform Embed Error: Element with ID "' + elementId + '" not found');
                return;
            }

            // ローディング表示
            targetElement.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">読み込み中...</div>';

            // ベースURLを自動検出（スクリプトのURLから）
            if (!this.baseUrl) {
                this.baseUrl = this._detectBaseUrl();
            }

            // AJAXでフォーム設定を取得
            this._fetchFormHtml(formId)
                .then(function(html) {
                    // フォームHTMLを注入
                    targetElement.innerHTML = html;

                    // フォーム送信イベントを設定
                    InformEmbed._setupFormSubmission(targetElement, formId);
                })
                .catch(function(error) {
                    console.error('Inform Embed Error:', error);
                    targetElement.innerHTML = '<div style="padding: 20px; background-color: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c00;">エラー: フォームの読み込みに失敗しました</div>';
                });
        },

        /**
         * ベースURLを自動検出
         * 
         * @private
         * @returns {string} ベースURL
         */
        _detectBaseUrl: function() {
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src;
                if (src && src.indexOf('embed.js') !== -1) {
                    // embed.jsのURLからベースURLを抽出
                    // 例: https://example.com/public/inform/embed.js -> https://example.com
                    const url = new URL(src);
                    return url.origin;
                }
            }
            return '';
        },

        /**
         * フォームHTMLを取得
         * 
         * @private
         * @param {number} formId - フォームID
         * @returns {Promise<string>} フォームHTML
         */
        _fetchFormHtml: function(formId) {
            return new Promise(function(resolve, reject) {
                const url = InformEmbed.baseUrl + '/public/inform/embed.php?form_id=' + formId;

                // XMLHttpRequestを使用（IE11互換性のため）
                const xhr = new XMLHttpRequest();
                xhr.open('GET', url, true);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        resolve(xhr.responseText);
                    } else {
                        reject(new Error('HTTP Error: ' + xhr.status));
                    }
                };
                xhr.onerror = function() {
                    reject(new Error('Network Error'));
                };
                xhr.send();
            });
        },

        /**
         * フォーム送信処理を設定
         * 
         * @private
         * @param {HTMLElement} container - フォームコンテナ要素
         * @param {number} formId - フォームID
         */
        _setupFormSubmission: function(container, formId) {
            const form = container.querySelector('#inform-form');
            if (!form) {
                console.error('Inform Embed Error: Form element not found');
                return;
            }

            // フォーム送信イベントをオーバーライド
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                const successMessage = container.querySelector('#success-message');
                const errorMessage = container.querySelector('#error-message');

                // ボタンを無効化
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = '送信中...';
                }

                // メッセージを非表示
                if (successMessage) {
                    successMessage.classList.add('hidden');
                }
                if (errorMessage) {
                    errorMessage.classList.add('hidden');
                }

                // FormDataを作成
                const formData = new FormData(form);

                // AJAX送信
                InformEmbed._submitForm(form.action, formData)
                    .then(function(data) {
                        if (data.success) {
                            // 成功時
                            if (successMessage) {
                                successMessage.textContent = data.message;
                                successMessage.classList.remove('hidden');
                            }
                            form.reset();

                            // リダイレクトURLが設定されている場合
                            if (data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = data.redirect_url;
                                }, 2000);
                            }
                        } else {
                            // エラー時
                            if (errorMessage) {
                                errorMessage.innerHTML = data.message || 'エラーが発生しました';
                                errorMessage.classList.remove('hidden');
                            }
                        }
                    })
                    .catch(function(error) {
                        console.error('Inform Embed Error:', error);
                        if (errorMessage) {
                            errorMessage.textContent = 'ネットワークエラーが発生しました';
                            errorMessage.classList.remove('hidden');
                        }
                    })
                    .finally(function() {
                        // ボタンを有効化
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = '送信';
                        }
                    });
            });
        },

        /**
         * フォームを送信
         * 
         * @private
         * @param {string} url - 送信先URL
         * @param {FormData} formData - フォームデータ
         * @returns {Promise<object>} レスポンスデータ
         */
        _submitForm: function(url, formData) {
            return new Promise(function(resolve, reject) {
                // XMLHttpRequestを使用（IE11互換性のため）
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            resolve(data);
                        } catch (e) {
                            reject(new Error('Invalid JSON response'));
                        }
                    } else {
                        reject(new Error('HTTP Error: ' + xhr.status));
                    }
                };
                xhr.onerror = function() {
                    reject(new Error('Network Error'));
                };
                xhr.send(formData);
            });
        }
    };

    // グローバルスコープに公開
    window.InformEmbed = InformEmbed;

})(window);
