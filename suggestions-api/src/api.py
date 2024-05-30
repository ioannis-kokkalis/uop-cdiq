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
import time

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
classifier_gr = pipeline("zero-shot-classification", model="lighteternal/nli-xlm-r-greek")
translator = Translator()

candidate_labels = ['finance', 'information & technology', 'business management']
candidate_labels_gr = [
    'οικονομικά', 'τεχνολογία πληροφορίας', 'διοίκηση επιχειρήσεων', 'μάρκετινγκ', 'πληροφορική',
    'επιχειρησιακή διοίκηση'
]

tag_mapping = {
    'οικονομικά': 'finance',
    'τεχνολογία πληροφορίας': 'information & technology',
    'διοίκηση επιχειρήσεων': 'business management',
    'μάρκετινγκ': 'business management',
    'πληροφορική': 'information & technology',
    'επιχειρησιακή διοίκηση': 'business management'
}


def translate_and_check_tags(tags):
    translated_tags = [tag_mapping.get(tag, tag) for tag in tags]

    # Get unique translated tags
    unique_tags = list(set(translated_tags))

    # Check for individual tags or combinations
    result_tags = set()
    for tag in unique_tags:
        if tag in candidate_labels:
            result_tags.add(tag)
    return {"tags": list(result_tags)}


@app.post("/classify_resume")
async def classify_resume(file: UploadFile = File(...)):
    start_time = time.time()  # Start timer

    # Save the file to a temporary location
    file_path = f"/tmp/{file.filename}"
    with open(file_path, "wb") as buffer:
        # shutil.copyfileobj(file.file, buffer)
        await run_in_threadpool(shutil.copyfileobj, file.file, buffer)

    # Ensure file is saved properly
    if not os.path.exists(file_path):
        raise HTTPException(status_code=500, detail="Failed to save the uploaded file")
    logging.info(f"Successfully saved file to {file_path}")

    # Process the resume and get the result
    # result = await run_in_threadpool(process_resume, file_path)
    result = process_resume(file_path)

    # Delete the file after processing
    os.remove(file_path)
    end_time = time.time()  # End timer
    total_time = end_time - start_time
    logging.info(f"Total processing time: {total_time:.2f} seconds")
    return JSONResponse(content=result)


def process_resume(file_path):
    try:
        # Extract text from the resume
        text_extractor = TextExtractor(file_path)
        text = text_extractor.extract_text()
        if not text:
            raise HTTPException(status_code=500, detail="Failed to extract text from the resume")
        logging.info(f"Extracted text from the resume: {text}")

        tokens = text.split()
        limited_text = tokens[:300]
        logging.info(f"Capped text: {limited_text}, length: {len(limited_text)}")

        limited_text = ' '.join(limited_text)

        # Preprocess the text
        text_preprocessor = TextPreprocessor(limited_text)
        preprocessed_text = text_preprocessor.preprocess_text()
        if not preprocessed_text:
            raise HTTPException(status_code=500, detail="Failed to preprocess the text")
        logging.info(f"Preprocessed text: {preprocessed_text}")

        # Choose appropriate classifier
        if detect(text) == 'el':
            output =  classifier_gr(preprocessed_text, candidate_labels_gr, multi_label=True)
        else:
            output = classifier(preprocessed_text, candidate_labels, multi_label=True)

        if not output:
            raise HTTPException(status_code=500, detail="Failed to classify the text")
        logging.info(f"Classification: {output}")

        labels = output['labels']
        scores = output['scores']

        # Determine the tags based on scores
        tags = [labels[0]]  # Always include the top label
        if scores[1] > 0.7 * scores[0]:  # If the second label's score is close to the first label's score
            tags.append(labels[1])

        result = translate_and_check_tags(tags)
        return result

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
