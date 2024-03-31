import shutil
from transformers import pipeline
from fastapi import FastAPI, UploadFile, File, HTTPException
import logging
import os
from TextExtractor import TextExtractor
from TextPreprocessor import TextPreprocessor
from Translator import Translator
from langdetect import detect

app = FastAPI()

# Set up extensive logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)

# Load the summarizer and classifier
logging.info("Loading the summarizer...")
summarizer = pipeline("summarization", model="philschmid/bart-large-cnn-samsum", truncation=True)
logging.info("Done!")
logging.info("Loading the classifier...")
classifier = pipeline("zero-shot-classification", model="facebook/bart-large-mnli")
logging.info("Done!")
translator = Translator()
candidate_labels = ['finance', 'healthcare', 'information & technology', 'business management']


@app.post("/classify_resume")
async def classify_resume(file: UploadFile = File(...)):
    try:
        # Save the file to a temporary location
        file_path = f"/tmp/{file.filename}"
        with open(file_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
        
        # Ensure file is saved properly
        if not os.path.exists(file_path):
            raise HTTPException(status_code=500, detail="Failed to save the uploaded file")
        logging.info(f"Successfully saved file to {file_path}")

        # Extract text from the resume
        text_extractor = TextExtractor(file_path)
        text = text_extractor.extract_text()
        if not text:
            raise HTTPException(status_code=500, detail="Failed to extract text from the resume")
        logging.info(f"Extracted text from the resume: {text}")
        
        translated_text = text.split()

        if detect(text) == 'el':
            try:
                # Translate the text to english

                # split in 2x512 tokens max, per the max input of the summarizer (1024)
                if len(translated_text) > 1024:
                    translated_text = ' '.join(translated_text[:1023])
                    translated_text = translator.translate_large_text_to_english(translated_text)
                else:
                    translated_text = ' '.join(translated_text)
                    translated_text = translator.translate_to_english(text)

                logging.info(f"Translated text to english: {translated_text}")
            except HTTPException as e:
                raise HTTPException(status_code=e.status_code, detail=e.detail)
        else:
            translated_text = ' '.join(translated_text)

        # Preprocess the text
        text_preprocessor = TextPreprocessor(translated_text)
        preprocessed_text = text_preprocessor.preprocess_text()
        if not preprocessed_text:
            raise HTTPException(status_code=500, detail="Failed to preprocess the text")
        logging.info(f"Preprocessed text: {preprocessed_text}")


        # Summarize the text
        # summary = summarizer(preprocessed_text, max_length=150, min_length=50, length_penalty=2.0, num_beams=4, early_stopping=True)
        # summary = summarizer(preprocessed_text, max_length=150, min_length=50, length_penalty=2.0, num_beams=4, early_stopping=False)
        summary = summarizer(preprocessed_text, max_length=100, min_length=30)
        logging.info(f"Summary from the preprocessed text: {summary}")


        if not summary:
            raise HTTPException(status_code=500, detail="Failed to summarize the text")

        # Classify the summary
        output = classifier(summary[0]['summary_text'], candidate_labels)
        if not output:
            raise HTTPException(status_code=500, detail="Failed to classify the summary")
        logging.info(f"Classification: {output}")

        return {
            "summary": summary[0]['summary_text'],
            "classification": list(zip(output['labels'], output['scores']))
        }

    except FileNotFoundError:
        raise HTTPException(status_code=400, detail="File not found")
    except Exception as e:
        logging.error(f"An error occurred: {e}")
        raise HTTPException(status_code=500, detail="Internal Server Error")


