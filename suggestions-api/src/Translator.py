from transformers import pipeline
from fastapi import HTTPException
from transformers import MarianMTModel, MarianTokenizer

class Translator:
    def __init__(self):
        try:
            self.model = MarianMTModel.from_pretrained("Helsinki-NLP/opus-mt-grk-en")
            self.tokenizer = MarianTokenizer.from_pretrained("Helsinki-NLP/opus-mt-grk-en")
            self.max_length = 512
        except Exception as e:
            raise HTTPException(status_code=500, detail="Error initializing translation pipeline.")


    def translate_to_english(self, text: str):
        """Translate the text from Greek to English."""
        try:
            tokens = self.tokenizer.encode(text, return_tensors="pt", max_length=self.max_length, truncation=True)
            output_tokens = self.model.generate(tokens, max_length=self.max_length, num_beams=4, early_stopping=True)
            output_text = self.tokenizer.decode(output_tokens[0], skip_special_tokens=True)
            return output_text
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

