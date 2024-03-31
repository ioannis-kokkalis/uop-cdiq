from transformers import pipeline
from fastapi import HTTPException


class Translator:
    def __init__(self):
        try:
            self.model = pipeline("translation", model="Helsinki-NLP/opus-mt-grk-en")
        except Exception as e:
            raise HTTPException(status_code=500, detail="Error initializing translation pipeline.")


    def translate_to_english(self, text: str):
        """Translate the text from Greek to English."""
        try:
            translated_text = self.model(text)
            return translated_text[0]['translation_text']
        except Exception as e:
            raise HTTPException(status_code=500, detail="Translation process failed.")

    def translate_large_text_to_english(self, text: str, max_length=512):
        
        # Split the text into chunks that are within the model's max length
        chunks = [text[i:i+max_length] for i in range(0, len(text), max_length)]

        # Translate each chunk
        translated_chunks = [self.model(chunk)[0]['translation_text'] for chunk in chunks]

        # Combine the translated chunks
        combined_translation = ' '.join(translated_chunks)

        return combined_translation

