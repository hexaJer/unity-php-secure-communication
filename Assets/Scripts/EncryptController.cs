using System;
using System.Collections;
using System.Security.Cryptography;
using System.Text;
using UnityEngine;
using UnityEngine.Networking;
using UnityEngine.UI;


public class EncryptController : MonoBehaviour
{
    public string ScriptUrl = "http://example.com/encrypt.php";

    public InputField ClearTextInputField;
    public InputField EncryptedTextInputField;
    
    public InputField ClearResponseInputField;
    public Text PublicKeyText;

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

    public void OnKeygenButtonClick()
    {
        StartCoroutine(GenerateRsaKeyPair());
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

        using (var www = UnityWebRequest.Post(ScriptUrl, form))
        {
            yield return www.SendWebRequest();

            if (www.isNetworkError)
            {
                Debug.LogError(www.error);
            }
            else
            {
                //var sb = new StringBuilder();
                //foreach (var dict in www.GetResponseHeaders())
                //{
                //    sb.Append(dict.Key).Append(": \t[").Append(dict.Value).Append("]\n");
                //}

                //// Print Headers
                //Debug.Log(sb.ToString());

                // Print Body
                Debug.Log(www.downloadHandler.text);
                PublicKeyText.text = www.downloadHandler.text;
            }
        }
    }

    IEnumerator SendEncryptedText(string text)
    {
        var form = new WWWForm();
        form.AddField("decrypt", text);

        using (var www = UnityWebRequest.Post(ScriptUrl, form))
        {
            yield return www.SendWebRequest();

            if (www.isNetworkError)
            {
                Debug.LogError(www.error);
            }
            else
            {
                Debug.Log(www.downloadHandler.text);
                ClearResponseInputField.text = www.downloadHandler.text;
            }
        }
    }
}
