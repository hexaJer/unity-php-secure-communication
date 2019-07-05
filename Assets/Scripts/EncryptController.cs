using System;
using System.Collections;
using System.Security.Cryptography;
using System.Text;
using UnityEngine;
using UnityEngine.Analytics;
using UnityEngine.Networking;
using UnityEngine.UI;


public class EncryptController : MonoBehaviour
{
    public string ScriptUrl = "http://example.com/encrypt.php";

    public InputField ClearTextInputField;
    public InputField EncryptedTextInputField;
    public InputField ClearResponseInputField;

    public Text SessionIdText;
    public Text PublicKeyText;

    public void OnKeygenButtonClick()
    {
        StartCoroutine(GenerateRsaKeyPair());
    }

    public void OnEncryptButtonClick()
    {
        if (PublicKeyText.text.Length < 100)
        {
            Debug.LogError("You must generate RSA key pair before");
            return;
        }
        var csp = new RSACryptoServiceProvider(1024);
        csp.FromXmlString(PublicKeyText.text);
        var plainTextData = ClearTextInputField.text;
        var bytesPlainTextData = Encoding.Unicode.GetBytes(plainTextData);
        var bytesCypherText = csp.Encrypt(bytesPlainTextData, false);
        var cypherText = Convert.ToBase64String(bytesCypherText);
        EncryptedTextInputField.text = cypherText;
    }

    public void OnDecryptButtonClick()
    {
        if (PublicKeyText.text.Length < 100)
        {
            Debug.LogError("You must generate RSA key pair before");
            return;
        }
        StartCoroutine(SendEncryptedText(EncryptedTextInputField.text));
    }

    IEnumerator GenerateRsaKeyPair()
    {
        var form = new WWWForm();
        form.AddField("keygen", 1);
        form.AddField("session_id", AnalyticsSessionInfo.sessionId.ToString());
        SessionIdText.text = "sessionId=" + AnalyticsSessionInfo.sessionId.ToString();

        using (var www = UnityWebRequest.Post(ScriptUrl, form))
        {
            yield return www.SendWebRequest();
            if (www.isNetworkError || www.isHttpError)
            {
                Debug.LogError(www.url + " : " + www.error);
                Debug.LogWarning("Have you defined ScriptUrl correctly?");
            }
            else
            {
                LogHeaders(www);
                
                Debug.Log(www.downloadHandler.text);
                PublicKeyText.text = www.downloadHandler.text;
            }
        }
    }

    IEnumerator SendEncryptedText(string text)
    {
        var form = new WWWForm();
        form.AddField("decrypt", text);
        form.AddField("session_id", AnalyticsSessionInfo.sessionId.ToString());
        SessionIdText.text = "sessionId=" + AnalyticsSessionInfo.sessionId.ToString();

        using (var www = UnityWebRequest.Post(ScriptUrl, form))
        {
            //www.SetRequestHeader("cookie", SessionIdText.text);
            yield return www.SendWebRequest();

            if (www.isNetworkError || www.isHttpError)
            {
                Debug.LogError(www.url + " : " + www.error);
            }
            else
            {
                LogHeaders(www);

                // Print Body
                Debug.Log(www.downloadHandler.text);
                ClearResponseInputField.text = www.downloadHandler.text;
            }
        }
    }

    private void LogHeaders(UnityWebRequest www)
    {
        var sb = new StringBuilder();
        foreach (var dict in www.GetResponseHeaders())
        {
            sb.Append(dict.Key).Append(": \t[").Append(dict.Value).Append("]\n");

            if (dict.Key == "Set-Cookie")
            {
                SessionIdText.text = dict.Value + " (from response headers)";
            }
        }
        Debug.Log(sb.ToString());
    }
}
