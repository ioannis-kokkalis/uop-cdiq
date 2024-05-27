from fastapi import FastAPI, UploadFile, File, HTTPException
from transformers import pipeline
import logging
import shutil
import os
from langdetect import detect
from TextExtractor import TextExtractor
from TextPreprocessor import TextPreprocessor
from Translator import Translator
from fastapi.responses import JSONResponse
from fastapi.concurrency import run_in_threadpool


app = FastAPI()

# Set up extensive logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)

# Load the summarizer and classifier
# logging.info("Loading the summarizer...")
# summarizer = pipeline("summarization", model="philschmid/bart-large-cnn-samsum", truncation=True)
# logging.info("Done!")
logging.info("Loading the classifier...")
classifier = pipeline("zero-shot-classification", model="facebook/bart-large-mnli")
logging.info("Done!")
translator = Translator()
candidate_labels = ['finance', 'information & technology', 'business management']


@app.post("/classify_resume")
async def classify_resume(file: UploadFile = File(...)):
    # Save the file to a temporary location
    file_path = f"/tmp/{file.filename}"
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    # Ensure file is saved properly
    if not os.path.exists(file_path):
        raise HTTPException(status_code=500, detail="Failed to save the uploaded file")
    logging.info(f"Successfully saved file to {file_path}")

    # Process the resume and get the result
    # result = process_resume(file_path)
    result = await run_in_threadpool(process_resume, file_path)

    # Delete the file after processing
    os.remove(file_path)

    return JSONResponse(content=result)


def process_resume(file_path):
    try:
        # Extract text from the resume
        text_extractor = TextExtractor(file_path)
        text = text_extractor.extract_text()
        if not text:
            raise HTTPException(status_code=500, detail="Failed to extract text from the resume")
        logging.info(f"Extracted text from the resume: {text}")

        translated_text = text.split()

        if detect(text) == 'el':
            try:
                # Translate the text to English
                if len(translated_text) > 1024:
                    translated_text = ' '.join(translated_text[:1023])
                    translated_text = translator.translate_large_text_to_english(translated_text)
                else:
                    translated_text = ' '.join(translated_text)
                    translated_text = translator.translate_to_english(text)

                logging.info(f"Translated text to English: {translated_text}")
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
        # summary = summarizer(preprocessed_text, max_length=100, min_length=30)
        # logging.info(f"Summary from the preprocessed text: {summary}")

        # if not summary:
        #    raise HTTPException(status_code=500, detail="Failed to summarize the text")

        # Classify the summary, calculate each label score independently
        # output = classifier(summary[0]['summary_text'], candidate_labels, multi_label=True)
        output = classifier(preprocessed_text, candidate_labels, multi_label=True)
        if not output:
            raise HTTPException(status_code=500, detail="Failed to classify the summary")
        logging.info(f"Classification: {output}")

        labels = output['labels']
        scores = output['scores']

        # Determine the tags based on scores
        tags = [labels[0]]  # Always include the top label
        if scores[1] > 0.7 * scores[0]:  # If the second label's score is close to the first label's score
            tags.append(labels[1])

        return {
            "tags": tags
        }

    except FileNotFoundError:
        raise HTTPException(status_code=400, detail="File not found")
    except Exception as e:
        logging.error(f"An error occurred: {e}")
        raise HTTPException(status_code=500, detail="Internal Server Error")


@app.post("/classify_job_descriptions")
async def classify_job_descriptions(job_descriptions: list[dict]):
    results = []
    for job in job_descriptions:
        description = job['description']
        logging.info(f"Processing job description: {description}")

        translated_text = description.split()

        try:
            if detect(description) == 'el':
                try:
                    # Translate the text to English
                    if len(translated_text) > 1024:
                        translated_text = ' '.join(translated_text[:1023])
                        translated_text = translator.translate_large_text_to_english(translated_text)
                    else:
                        translated_text = ' '.join(translated_text)
                        translated_text = translator.translate_to_english(description)

                    logging.info(f"Translated text to English: {translated_text}")
                except HTTPException as e:
                    raise HTTPException(status_code=e.status_code, detail=e.detail)
            else:
                translated_text = ' '.join(translated_text)

            # Preprocess the description
            text_preprocessor = TextPreprocessor(translated_text)
            preprocessed_text = text_preprocessor.preprocess_text()
            if not preprocessed_text:
                raise HTTPException(status_code=500, detail="Failed to preprocess the text")
            logging.info(f"Preprocessed job description: {preprocessed_text}")

            # Summarize the text
            # summary = summarizer(preprocessed_text, max_length=100, min_length=30)
            # logging.info(f"Summary from the job description: {summary}")

            # if not summary:
            #     raise HTTPException(status_code=500, detail="Failed to summarize the text")

            # Classify the summary
            # output = classifier(summary[0]['summary_text'], candidate_labels)
            output = classifier(preprocessed_text, candidate_labels, multi_label=True)
            if not output:
                raise HTTPException(status_code=500, detail="Failed to classify the summary")
            logging.info(f"Classification: {output}")

            # Get the highest scored label
            highest_score = max(output['scores'])
            highest_label = output['labels'][output['scores'].index(highest_score)]

            results.append({"id": job['id'], "tag": highest_label})
        except Exception as e:
            logging.error(f"An error occurred while processing job description {job['id']}: {e}")
            raise HTTPException(status_code=500, detail="Internal Server Error")

    return JSONResponse(content=results)
